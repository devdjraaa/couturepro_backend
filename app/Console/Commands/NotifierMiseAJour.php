<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotification;
use App\Models\Atelier;
use App\Models\NotificationSysteme;
use App\Models\Proprietaire;
use App\Models\VitrineSetting;
use Illuminate\Console\Command;

/**
 * Prévient les professionnels qu'une mise à jour est disponible.
 *
 * Jusqu'ici, une version publiée n'était visible que dans « Quoi de neuf » —
 * un écran qu'il faut penser à ouvrir. Personne n'était donc AVERTI : ni pour
 * une mise à jour à chaud (OTA), ni pour une grosse version.
 *
 * La commande dépose une notification dans l'application ET envoie une
 * notification système à ceux qui ont un appareil enregistré. Le texte reprend
 * le journal des nouveautés quand la version y figure : annoncer « une mise à
 * jour est disponible » sans dire ce qu'elle apporte ne sert personne.
 *
 *   php artisan app:notifier-maj 1.0.139
 *   php artisan app:notifier-maj 2.0 --majeure
 */
class NotifierMiseAJour extends Command
{
    protected $signature = 'app:notifier-maj
                            {version : Numéro de la version publiée}
                            {--majeure : Version native (installation requise) plutôt qu\'une mise à jour à chaud}';

    protected $description = "Prévient les professionnels qu'une mise à jour est disponible";

    public function handle(): int
    {
        $version = (string) $this->argument('version');
        $majeure = (bool) $this->option('majeure');

        // Le titre et le détail viennent du journal des nouveautés quand la
        // version y est décrite : c'est là que se trouve le vrai contenu.
        $entree = collect(VitrineSetting::journalMaj())
            ->first(fn ($e) => ($e['version'] ?? null) === $version);

        $titre = $majeure
            ? "Nouvelle version disponible ({$version})"
            : ($entree['titre'] ?? "Gextimo a été mis à jour ({$version})");

        $contenu = $entree['titre'] ?? null;
        if (! empty($entree['lignes'])) {
            $contenu = implode(' · ', array_slice($entree['lignes'], 0, 3));
        }
        $contenu ??= $majeure
            ? "Installez la nouvelle version pour en profiter."
            : "Les nouveautés sont dans « Quoi de neuf ».";

        $ateliers = Atelier::query()->pluck('id');
        if ($ateliers->isEmpty()) {
            $this->warn('Aucun atelier : rien à notifier.');

            return self::SUCCESS;
        }

        // Une notification par atelier, pour qu'elle apparaisse dans l'app.
        $lignes = $ateliers->map(fn ($id) => [
            'id'         => (string) \Illuminate\Support\Str::uuid(),
            'atelier_id' => $id,
            'titre'      => $titre,
            'contenu'    => $contenu,
            'type'       => 'mise_a_jour',
            'canal'      => 'notification',
            'lien'       => '/quoi-de-neuf',
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        foreach (array_chunk($lignes, 200) as $lot) {
            NotificationSysteme::insert($lot);
        }

        // Notification SYSTÈME : c'est elle qui sort de l'application. Sans
        // appareil enregistré on ne peut rien envoyer — ce n'est pas une erreur.
        $jetons = Proprietaire::query()
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->filter()
            ->unique();

        foreach ($jetons as $jeton) {
            SendPushNotification::dispatch($jeton, $titre, $contenu, [
                'type'    => 'mise_a_jour',
                'version' => $version,
                'lien'    => '/quoi-de-neuf',
            ]);
        }

        $this->info(sprintf(
            'Version %s : %d notification(s) déposée(s), %d appareil(s) prévenu(s).',
            $version,
            count($lignes),
            $jetons->count(),
        ));

        return self::SUCCESS;
    }
}
