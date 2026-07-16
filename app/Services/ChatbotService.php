<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Brief 16/07 (pt 1) : moteur du chatbot v1 — détection d'intention par mots-clés
// (base de connaissances gxt_chat_intents, éditable admin). Volontairement simple,
// déterministe et gratuit ; le jour où la direction veut un LLM, seul repondre()
// change (même signature), la mémoire/le feedback/le dashboard restent identiques.
class ChatbotService
{
    /** @return array{reponse: string, intention: string} */
    public function repondre(string $question, string $langue = 'fr'): array
    {
        $texte = $this->normaliser($question);
        $colonne = $langue === 'en' ? 'reponse_en' : 'reponse_fr';

        $meilleur = null;
        $meilleurScore = 0;

        foreach (DB::table('gxt_chat_intents')->where('actif', true)->get() as $intent) {
            $score = 0;
            foreach (json_decode($intent->mots_cles, true) ?: [] as $mot) {
                if (str_contains($texte, $this->normaliser($mot))) {
                    // Les expressions longues pèsent plus lourd qu'un mot isolé.
                    $score += 1 + substr_count($mot, ' ');
                }
            }
            if ($score > $meilleurScore
                || ($score === $meilleurScore && $score > 0 && $intent->priorite > ($meilleur->priorite ?? -1))) {
                $meilleur = $intent;
                $meilleurScore = $score;
            }
        }

        if ($meilleur && $meilleurScore > 0) {
            return ['reponse' => $meilleur->{$colonne}, 'intention' => $meilleur->code];
        }

        return [
            'reponse' => $langue === 'en'
                ? "I'm not sure I understood. You can rephrase, or write to support.gextimo@novafriq.africa — we reply within 48 h."
                : "Je ne suis pas sûr d'avoir compris. Vous pouvez reformuler, ou écrire à support.gextimo@novafriq.africa — nous répondons sous 48 h.",
            'intention' => 'fallback',
        ];
    }

    /** minuscules + sans accents : "Où en est ma Commande ?" → "ou en est ma commande ?" */
    private function normaliser(string $s): string
    {
        return Str::ascii(mb_strtolower(trim($s)));
    }
}
