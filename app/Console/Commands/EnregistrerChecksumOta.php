<?php

namespace App\Console\Commands;

use App\Models\VitrineSetting;
use Illuminate\Console\Command;

/**
 * Enregistre l'empreinte SHA-256 d'un paquet OTA fraîchement publié.
 *
 * Appelée par `release.sh` juste après le dépôt du fichier sur le serveur,
 * avec l'empreinte calculée sur la machine de build — donc AVANT tout transfert
 * réseau qui pourrait corrompre le fichier. `AppVersionController::updates()`
 * la sert ensuite au plugin, qui vérifie lui-même l'intégrité avant d'installer.
 *
 *   php artisan app:enregistrer-checksum-ota com.couturepro.app 1.0.144 <sha256>
 */
class EnregistrerChecksumOta extends Command
{
    protected $signature = 'app:enregistrer-checksum-ota {app_id} {version} {sha256}';

    protected $description = "Enregistre l'empreinte SHA-256 d'un paquet OTA publié";

    public function handle(): int
    {
        $sha256 = (string) $this->argument('sha256');

        if (! preg_match('/^[a-f0-9]{64}$/', $sha256)) {
            $this->error("Empreinte invalide (attendu : 64 caractères hexadécimaux) : {$sha256}");

            return self::FAILURE;
        }

        VitrineSetting::enregistrerChecksumOta(
            (string) $this->argument('app_id'),
            (string) $this->argument('version'),
            $sha256,
        );

        $this->info('Empreinte enregistrée.');

        return self::SUCCESS;
    }
}
