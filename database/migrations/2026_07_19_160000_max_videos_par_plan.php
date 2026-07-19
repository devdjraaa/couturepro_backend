<?php

use App\Models\NiveauConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * VID-2 — Nombre maximum de vidéos de présentation PAR PLAN.
 *
 * Avant : plafond de 50 codé en dur dans le contrôleur, identique pour tous, et
 * la fonctionnalité était réservée au plan Studio (`videos_presentation` à false
 * pour Gratuit et Atelier).
 *
 * Décision direction : Gratuit 1 · Atelier 3 · Studio 5. Les vidéos deviennent
 * donc accessibles à tous les plans, avec une limite chiffrée pilotée par la
 * configuration (plus rien en dur côté code).
 */
return new class extends Migration
{
    /** Limite par famille de plan (préfixe de clé). */
    private function limitePour(string $cle): int
    {
        return match (true) {
            str_starts_with($cle, 'master_')  => 5,   // Studio
            str_starts_with($cle, 'atelier_') => 3,   // Atelier
            default                           => 1,   // Gratuit et plans hérités
        };
    }

    public function up(): void
    {
        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];

            $config['max_videos']          = $this->limitePour($plan->cle);
            $config['videos_presentation'] = true;    // ouvert à tous, la limite fait foi

            $plan->update(['config' => $config]);
        }

        // Libellé de la nouvelle clé, pour l'admin et les messages de blocage.
        if (! DB::table('fonctionnalites')->where('cle', 'max_videos')->exists()) {
            DB::table('fonctionnalites')->insert([
                'cle'             => 'max_videos',
                'label'           => 'Vidéos de présentation',
                'description'     => 'Nombre de vidéos publiables sur le profil public',
                'type'            => 'numerique',
                'unite'           => 'vidéos',
                'categorie'       => 'communication',
                'is_actif'        => true,
                'ordre_affichage' => 130,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];
            unset($config['max_videos']);
            // Retour à l'état antérieur : réservé au plan Studio.
            $config['videos_presentation'] = str_starts_with($plan->cle, 'master_');
            $plan->update(['config' => $config]);
        }
    }
};
