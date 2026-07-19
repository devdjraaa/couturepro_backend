<?php

use App\Models\NiveauConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * PHOTO-4/5 — Quota de publications de réalisations par cycle d'abonnement.
 *
 * La direction fixe un quota maximum par formule (exemple donné : 5 / 10 / 20),
 * avec un cycle unique pour tous : reset le 22 de chaque mois à 00h00, heure de
 * Cotonou. Valeurs éditables en admin, comme tous les autres quotas.
 *
 * ⚠️ Ce sont les valeurs d'EXEMPLE du document de la direction. À confirmer avec
 * elle avant communication aux utilisateurs.
 */
return new class extends Migration
{
    private function quotaPour(string $cle): int
    {
        return match (true) {
            str_starts_with($cle, 'master_')  => 20,  // Studio
            str_starts_with($cle, 'atelier_') => 10,  // Atelier
            default                           => 5,   // Gratuit et plans hérités
        };
    }

    public function up(): void
    {
        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];
            $config['max_realisations_cycle'] = $this->quotaPour($plan->cle);
            $plan->update(['config' => $config]);
        }

        if (! DB::table('fonctionnalites')->where('cle', 'max_realisations_cycle')->exists()) {
            DB::table('fonctionnalites')->insert([
                'cle'             => 'max_realisations_cycle',
                'label'           => 'Réalisations par cycle',
                'description'     => 'Nombre de réalisations publiables par cycle (renouvellement le 22 de chaque mois)',
                'type'            => 'numerique',
                'unite'           => 'publications',
                'categorie'       => 'module',
                'is_actif'        => true,
                'ordre_affichage' => 131,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];
            unset($config['max_realisations_cycle']);
            $plan->update(['config' => $config]);
        }
        DB::table('fonctionnalites')->where('cle', 'max_realisations_cycle')->delete();
    }
};
