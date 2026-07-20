<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        // 2e étage : mini-IA locale (Ollama) ancrée sur la base de connaissances Gextimo
        // (vitrine_settings.chatbot_contexte, éditable admin). Repli silencieux si indisponible.
        if ($reponseIa = $this->interrogerIaLocale($question, $langue)) {
            return ['reponse' => $reponseIa, 'intention' => 'ia_locale'];
        }

        return [
            'reponse' => $langue === 'en'
                ? "I am not certain I understood your request. Could you rephrase it? You can also write to support.gextimo@novafriq.africa, we reply within 48 hours."
                : "Je ne suis pas certain d'avoir bien compris votre demande. Pouvez-vous la reformuler ? Vous pouvez aussi écrire à support.gextimo@novafriq.africa, nous répondons sous 48 heures.",
            'intention' => 'fallback',
        ];
    }

    /**
     * Interroge la mini-IA locale, cadrée par la base de connaissances (jamais d'invention :
     * si l'info n'est pas dans le contexte, elle oriente vers le support). Retourne null si
     * Ollama est injoignable/lent — le chatbot retombe alors sur le fallback classique.
     */
    private function interrogerIaLocale(string $question, string $langue): ?string
    {
        $contexte = DB::table('vitrine_settings')
            ->where('cle', 'chatbot_contexte')->value('valeur');
        $contexte = $contexte ? (json_decode($contexte, true)['texte'] ?? null) : null;
        if (! $contexte) {
            return null;
        }

        $consigneLangue = $langue === 'en' ? 'Answer in English.' : 'Réponds en français.';
        $system = "Tu es Makila AI, l'assistant intelligent de Gextimo (signature : L'intelligence au service de la mode africaine). Ton chaleureux, précis, passionné de mode africaine et accessible ; vouvoiement ; phrases courtes ; termine par une action concrète quand c'est pertinent ; n'utilise JAMAIS le tiret long. Réponds UNIQUEMENT à partir du CONTEXTE ci-dessous, "
            ."en 2 à 4 phrases claires et aimables. N'invente jamais de prix, de délai ou de fonctionnalité, "
            ."et ne promets RIEN au nom de Gextimo ou des créateurs : les détails (prix, livraison, délais) "
            ."se conviennent directement avec le designer. Si la réponse n'est pas dans le contexte, dis-le "
            ."et oriente vers support.gextimo@novafriq.africa. "
            // 20/07 : hors des heures d'équipe, Makila relaie au lieu de promettre un
            // humain immédiat. Parenthèses OBLIGATOIRES : « . » lie plus fort que « ?: »
            // — sans elles, tout le prompt s'effondrait en chaîne vide.
            .(\App\Models\VitrineSetting::equipeEnLigne()
                ? ""
                : "L'équipe humaine est actuellement hors ligne : si la demande nécessite une personne (litige, réclamation, cas particulier), dis que tu la notes et la transmets à l'équipe, qui répondra dès sa reprise à "
                    .\App\Models\VitrineSetting::equipeHoraires()['debut']."h. Ne promets jamais de réponse immédiate d'un humain. ")
            .$consigneLangue."\n\nCONTEXTE :\n".$contexte;

        try {
            $resp = Http::timeout(45)
                ->post(rtrim(config('services.ollama.url'), '/').'/api/chat', [
                    'model'      => config('services.ollama.model'),
                    'stream'     => false,
                    'keep_alive' => '10m',
                    'options'    => ['temperature' => 0.2, 'num_predict' => 220],
                    'messages'   => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => mb_substr($question, 0, 600)],
                    ],
                ]);
            $texte = $resp->successful() ? trim((string) $resp->json('message.content')) : '';

            return $texte !== '' ? $texte : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** minuscules + sans accents : "Où en est ma Commande ?" → "ou en est ma commande ?" */
    private function normaliser(string $s): string
    {
        return Str::ascii(mb_strtolower(trim($s)));
    }
}
