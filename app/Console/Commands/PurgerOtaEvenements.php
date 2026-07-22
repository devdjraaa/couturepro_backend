<?php

namespace App\Console\Commands;

use App\Models\OtaEvenement;
use Illuminate\Console\Command;

/** Purge les événements OTA de plus de 90 jours — un journal, pas un historique permanent. */
class PurgerOtaEvenements extends Command
{
    protected $signature = 'ota:purger-evenements';

    protected $description = 'Supprime les événements OTA de plus de 90 jours';

    public function handle(): int
    {
        $n = OtaEvenement::where('created_at', '<', now()->subDays(90))->delete();
        $this->info("{$n} événement(s) OTA supprimé(s).");

        return self::SUCCESS;
    }
}
