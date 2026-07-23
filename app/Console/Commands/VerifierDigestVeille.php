<?php

namespace App\Console\Commands;

use App\Models\VitrineSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Vérifie que le digest de veille est bien parti ce matin.
 *
 * Le digest est envoyé par n8n, qui lit le relevé et le met en forme. Si quoi
 * que ce soit casse en chemin — API injoignable, SMTP en panne, n8n arrêté,
 * planificateur désactivé — personne ne reçoit rien, et personne ne s'en rend
 * compte : un courriel qui n'arrive pas ne fait aucun bruit.
 *
 * Une alerte posée DANS le workflow ne résout pas le problème, puisqu'elle ne
 * se déclenche pas quand c'est le workflow lui-même qui ne tourne pas — or
 * c'est la panne la plus probable. On inverse donc la charge de la preuve :
 * l'automate annonce sa réussite via `POST /api/veille/digest-envoye`, et
 * c'est l'ABSENCE de cette annonce qui alerte. Le silence devient un signal.
 *
 * Cette vérification tourne dans le planificateur Laravel, qui est surveillé
 * et indépendant de n8n : les deux ne tombent pas ensemble.
 */
class VerifierDigestVeille extends Command
{
    protected $signature = 'veille:verifier-digest';

    protected $description = "Alerte si le digest de veille n'est pas parti ce matin";

    public function handle(): int
    {
        // Deux surveillances distinctes, un seul passage quotidien : ce sont
        // toutes deux des « ce qui aurait du tourner et n'a pas tourne ».
        $this->verifierSilenceRenduRobots();

        $jour = now('Africa/Porto-Novo')->toDateString();
        $dernier = VitrineSetting::where('cle', 'veille_digest_dernier_envoi')->value('valeur');
        $dernierJour = is_array($dernier) ? ($dernier['jour'] ?? null) : null;

        if ($dernierJour === $jour) {
            // Le retour à la normale doit effacer le bandeau : un incident résolu
            // qui reste affiché apprend à ignorer les incidents.
            VitrineSetting::where('cle', 'veille_incident')->delete();

            $this->info(sprintf(
                'Digest du %s bien envoyé (%d article(s), %d retenu(s)).',
                $jour,
                $dernier['articles'] ?? 0,
                $dernier['retenus'] ?? 0,
            ));

            return self::SUCCESS;
        }

        // Le motif compte autant que la panne : « rien collecté » et « collecte
        // faite mais digest non parti » n'appellent pas la même vérification.
        $collectes = DB::table('gxt_veille_items')
            ->where('semaine', $jour)->count();

        $motif = $collectes === 0
            ? "la collecte de la nuit n'a rien enregistré — vérifier le planificateur et les sources"
            : "la collecte a enregistré {$collectes} article(s), mais l'automate n'a pas confirmé l'envoi — vérifier n8n et le SMTP";

        $message = "Digest de veille non parti le {$jour} : {$motif}.";
        $this->warn($message);

        // Journalisé d'abord : la notification dépend de la base et du worker,
        // le journal non. Une alerte qui a besoin que tout fonctionne pour
        // signaler que quelque chose ne fonctionne pas ne sert à rien.
        Log::warning($message, [
            'jour'            => $jour,
            'dernier_envoi'   => $dernierJour,
            'articles_du_jour' => $collectes,
        ]);

        $this->prevenirAdministrateurs($message, $jour);

        return self::SUCCESS;
    }

    /**
     * Le controle du rendu aux robots tourne-t-il encore ?
     *
     * Meme raisonnement que pour le digest : une surveillance qui s'arrete ne
     * previent personne, et c'est la panne la plus sournoise — on croit etre
     * couvert alors qu'on ne l'est plus. n8n a deja tourne SIX JOURS a l'arret
     * sans que rien ne le signale.
     *
     * Le controle passe toutes les 6 h : au-dela de 24 h de silence, il ne
     * tourne manifestement plus.
     */
    private function verifierSilenceRenduRobots(): void
    {
        $dernier = Cache::get(VeilleRenduRobots::CLE_DERNIER_PASSAGE);

        if ($dernier && now()->diffInHours(\Illuminate\Support\Carbon::parse($dernier)) < 24) {
            return;
        }

        $message = $dernier
            ? 'Le controle du rendu aux robots n\'a plus tourne depuis le '
              .\Illuminate\Support\Carbon::parse($dernier)->format('d/m/Y H:i').'.'
            : "Le controle du rendu aux robots n'a jamais tourne.";

        $this->warn($message);
        Log::warning($message);

        try {
            VitrineSetting::updateOrCreate(
                ['cle' => 'veille_incident_rendu_robots'],
                ['valeur' => ['message' => $message, 'horodate' => now()->toIso8601String()]],
            );
        } catch (\Throwable $e) {
            Log::error('Incident rendu robots non enregistre', ['erreur' => $e->getMessage()]);
        }
    }

    /**
     * Dépose l'incident là où l'écran de veille le lira.
     *
     * Volontairement pas par courriel : si le digest n'est pas parti, la voie
     * du courriel est justement celle qu'on soupçonne.
     *
     * Un seul enregistrement, écrasé à chaque fois : c'est un ÉTAT courant, pas
     * un journal. Empiler un incident par jour finirait par les rendre tous
     * invisibles, et le journal applicatif garde déjà l'historique.
     */
    private function prevenirAdministrateurs(string $message, string $jour): void
    {
        try {
            VitrineSetting::updateOrCreate(
                ['cle' => 'veille_incident'],
                ['valeur' => ['jour' => $jour, 'message' => $message, 'horodate' => now()->toIso8601String()]],
            );
            $this->info('Incident enregistré, visible sur l\'écran de veille.');
        } catch (\Throwable $e) {
            // La base est en cause : le journal a déjà reçu l'essentiel.
            $this->error('Incident non enregistré : ' . $e->getMessage());
        }
    }
}
