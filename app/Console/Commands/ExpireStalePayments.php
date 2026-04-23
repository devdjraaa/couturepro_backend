<?php

namespace App\Console\Commands;

use App\Models\Paiement;
use Illuminate\Console\Command;

class ExpireStalePayments extends Command
{
    protected $signature   = 'payments:expire-stale';
    protected $description = 'Passe en expired les paiements pending dont expires_at est dépassé';

    public function handle(): void
    {
        $count = Paiement::pending()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['statut' => 'expired']);

        $this->info("$count paiement(s) expiré(s).");
    }
}
