<?php

namespace App\Console\Commands;

use App\Models\Abonnement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessBonusExpiry extends Command
{
    protected $signature   = 'abonnements:process-bonus-expiry';
    protected $description = 'Termine les bonus expirés et reprend le décompte du principal';

    public function handle(): void
    {
        // Bonus dont bonus_timestamp_debut + 31 jours <= maintenant.
        // Condition équivalente et portable : debut <= maintenant - 31 jours
        // (DATE_ADD est du MySQL — plantait chaque nuit sur la prod PostgreSQL).
        $expires = Abonnement::where('bonus_actif', true)
            ->whereNotNull('bonus_timestamp_debut')
            ->where('bonus_timestamp_debut', '<=', now()->subDays(31))
            ->get();

        $count = 0;

        foreach ($expires as $abonnement) {
            DB::transaction(function () use ($abonnement) {
                // Recalculer timestamp_expiration depuis maintenant + jours_restants du principal
                $expiration = now()->addDays(max(0, $abonnement->jours_restants));

                $abonnement->update([
                    'bonus_actif'           => false,
                    'bonus_jours_restants'  => 0,
                    'bonus_timestamp_debut' => null,
                    'timestamp_expiration'  => $expiration,
                ]);
            });

            $count++;
        }

        $this->info("{$count} bonus terminé(s), principal repris.");
    }
}
