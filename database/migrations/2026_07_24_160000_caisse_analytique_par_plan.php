<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Échelonnement de la caisse par plan : le graphe analytique.
 *
 * Le journal de caisse (module_caisse) est le socle, présent dès le plan
 * Atelier. Le niveau supérieur — les graphes analytiques — est réservé aux
 * plans Studio. La direction demandait « des features pour chaque niveau ».
 *
 * Tout reste éditable ensuite depuis l'écran des plans (la clé est ajoutée au
 * référentiel des fonctionnalités).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Référentiel : la fonctionnalité apparaît, labellisée, dans l'admin.
        DB::table('fonctionnalites')->updateOrInsert(
            ['cle' => 'caisse_analytique'],
            [
                'label'           => 'Caisse — graphes analytiques',
                'description'     => 'Évolution des entrées/sorties sur 6 mois (niveau supérieur de la caisse)',
                'type'            => 'booleen',
                'categorie'       => 'module',
                'valeur_defaut'   => 'false',
                'ordre_affichage' => 10,
                'is_actif'        => true,
                'updated_at'      => now(),
                'created_at'      => now(),
            ],
        );

        // 2) Activer sur les plans Studio (master_*). Le cliché de configuration
        //    des abonnements en cours sur ces plans est aussi mis à jour, sinon
        //    l'atelier garderait l'ancien config figé.
        foreach (['master_mensuel', 'master_annuel'] as $cle) {
            $plan = DB::table('niveaux_config')->where('cle', $cle)->first();
            if (! $plan) {
                continue;
            }
            $config = json_decode($plan->config, true) ?: [];
            $config['caisse_analytique'] = true;
            DB::table('niveaux_config')->where('cle', $cle)->update(['config' => json_encode($config)]);

            DB::table('abonnements')->where('niveau_cle', $cle)->whereNotNull('config_snapshot')->get()
                ->each(function ($ab) {
                    $snap = json_decode($ab->config_snapshot, true) ?: [];
                    $snap['caisse_analytique'] = true;
                    DB::table('abonnements')->where('id', $ab->id)->update(['config_snapshot' => json_encode($snap)]);
                });
        }
    }

    public function down(): void
    {
        DB::table('fonctionnalites')->where('cle', 'caisse_analytique')->delete();
        foreach (['master_mensuel', 'master_annuel'] as $cle) {
            $plan = DB::table('niveaux_config')->where('cle', $cle)->first();
            if (! $plan) {
                continue;
            }
            $config = json_decode($plan->config, true) ?: [];
            unset($config['caisse_analytique']);
            DB::table('niveaux_config')->where('cle', $cle)->update(['config' => json_encode($config)]);
        }
    }
};
