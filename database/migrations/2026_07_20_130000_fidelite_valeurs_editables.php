<?php

use App\Models\NiveauConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Rend éditables en admin les deux derniers réglages de fidélité codés en dur.
 *
 * Les points par client/commande/activation et le seuil de conversion étaient déjà
 * modifiables (config du plan). En revanche la DURÉE du bonus (31 jours) et les
 * PALIERS étaient figés dans le code : impossible de recalibrer sans redéploiement.
 *
 * C'est bloquant au vu du constat du 20/07 : le programme est inatteignable
 * (375 points générés au total pour un seuil minimum de 10 000, 0 conversion).
 * La direction doit pouvoir ajuster elle-même, sans passer par un développeur.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];
            // Valeur actuelle conservée : aucun changement de comportement ici,
            // on rend seulement le réglage accessible.
            $config['bonus_jours_conversion'] = $config['bonus_jours_conversion'] ?? 31;
            $plan->update(['config' => $config]);
        }

        if (! DB::table('fonctionnalites')->where('cle', 'bonus_jours_conversion')->exists()) {
            DB::table('fonctionnalites')->insert([
                'cle'             => 'bonus_jours_conversion',
                'label'           => 'Jours de bonus par conversion',
                'description'     => 'Nombre de jours d\'abonnement offerts lorsque le seuil de points est converti',
                'type'            => 'numerique',
                'unite'           => 'jours',
                'categorie'       => 'fidelite',
                'valeur_defaut'   => '31',
                'is_actif'        => true,
                'ordre_affichage' => 133,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];
            unset($config['bonus_jours_conversion']);
            $plan->update(['config' => $config]);
        }
        DB::table('fonctionnalites')->where('cle', 'bonus_jours_conversion')->delete();
    }
};
