<?php

namespace App\Console\Commands;

use App\Models\Abonnement;
use App\Services\PaymentService;
use Illuminate\Console\Command;

// P53-55 : applique les échéances d'abonnement (downgrade programmé → plan inférieur,
// sinon expiration). Fiabilise le passage même sans consultation de l'app.
class AppliquerEcheancesAbonnements extends Command
{
    protected $signature = 'abonnements:appliquer-echeances';
    protected $description = 'Applique les downgrades programmés / expirations arrivés à échéance';

    public function handle(PaymentService $service): int
    {
        $dus = Abonnement::whereIn('statut', ['actif', 'essai'])
            ->whereNotNull('timestamp_expiration')
            ->where('timestamp_expiration', '<=', now())
            ->get();

        $down = 0;
        $exp  = 0;
        foreach ($dus as $abonnement) {
            $service->appliquerEcheance($abonnement) ? $down++ : $exp++;
        }

        $this->info("Échéances : {$down} downgrade(s), {$exp} expiration(s).");

        return self::SUCCESS;
    }
}
