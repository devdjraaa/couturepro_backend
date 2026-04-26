<?php

namespace App\Console\Commands;

use App\Models\Paiement;
use Illuminate\Console\Command;

class PurgeStalePayments extends Command
{
    protected $signature   = 'payments:purge-stale';
    protected $description = 'Annule les paiements pending > 1h, supprime ceux > 2h (sauf completed/valide)';

    public function handle(): void
    {
        // 1. Passer en "cancelled" les paiements pending depuis plus d'1 heure
        $cancelled = Paiement::where('statut', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->update([
                'statut'             => 'cancelled',
                'provider_metadata'  => \DB::raw("JSON_SET(COALESCE(provider_metadata, '{}'), '$.annulation_reason', 'timeout_automatique_1h')"),
            ]);

        // 2. Supprimer définitivement les paiements cancelled/expired/failed depuis plus de 2h
        $deleted = Paiement::whereIn('statut', ['cancelled', 'expired', 'failed'])
            ->where('created_at', '<', now()->subHours(2))
            ->delete();

        $this->info("Nettoyage paiements : {$cancelled} annulé(s), {$deleted} supprimé(s).");
    }
}
