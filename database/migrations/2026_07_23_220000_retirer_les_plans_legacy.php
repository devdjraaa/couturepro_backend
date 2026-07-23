<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retire les six plans d'abonnement hérités et rebascule ceux qui les utilisent.
 *
 * Ils étaient déjà DÉSACTIVÉS, donc invisibles dans l'administration — mais pas
 * inoffensifs : l'inscription attribuait encore `standard_mensuel` à TOUT
 * nouvel artisan. Trois ateliers en cours d'essai s'y trouvaient rattachés.
 *
 * Conséquence pour la direction : modifier le plan « Atelier » dans
 * l'administration ne changeait rien pour un seul artisan, puisqu'aucun n'y
 * était rattaché. Côté designer (Studio) tout fonctionnait — d'où le constat
 * « on ne peut modifier que les plans designer ».
 *
 * L'ordre compte : on rebascule AVANT de supprimer, sinon la clé étrangère
 * `abonnements.niveau_cle` casse.
 */
return new class extends Migration
{
    /** Ancien plan => plan actuel équivalent. */
    private const REMPLACEMENTS = [
        'standard_mensuel' => 'atelier_mensuel',
        'standard_annuel'  => 'atelier_annuel',
        'premium_mensuel'  => 'master_mensuel',
        'premium_annuel'   => 'master_annuel',
        'magnat_mensuel'   => 'master_mensuel',
        'magnat_annuel'    => 'master_annuel',
    ];

    /**
     * TOUTES les tables qui pointent vers `niveaux_config.cle`.
     *
     * Il y en a quatre, pas deux. N'en traiter qu'une partie ne se voit que sur
     * un environnement qui a de l'historique : la suppression passe là où les
     * autres tables sont vides, et échoue ailleurs sur la clé étrangère. Cette
     * liste est vérifiée contre le schéma, pas devinée.
     */
    private const REFERENCES = [
        'abonnements'             => 'niveau_cle',
        'transactions_abonnement' => 'niveau_cle',
        'paiements'               => 'niveau_cle',
        'offres_speciales'        => 'niveau_base_cle',
    ];

    public function up(): void
    {
        foreach (self::REMPLACEMENTS as $ancien => $actuel) {
            // Le plan de remplacement doit exister : sur un environnement où il
            // manque, on ne touche à rien plutôt que de créer des orphelins.
            $config = DB::table('niveaux_config')->where('cle', $actuel)->value('config');
            if ($config === null) {
                continue;
            }

            foreach (self::REFERENCES as $table => $colonne) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $maj = [$colonne => $actuel];
                if (Schema::hasColumn($table, 'updated_at')) {
                    $maj['updated_at'] = now();
                }
                // Le cliché de configuration suit le nouveau plan : sans cela,
                // l'atelier garderait les quotas de l'ancien.
                if ($table === 'abonnements') {
                    $maj['config_snapshot'] = $config;
                }

                DB::table($table)->where($colonne, $ancien)->update($maj);
            }
        }

        foreach (array_keys(self::REMPLACEMENTS) as $ancien) {
            // Garde-fou : on ne supprime que ce qui n'est plus référencé nulle
            // part. Mieux vaut laisser un plan mort qu'une base incohérente.
            $encoreUtilise = false;
            foreach (self::REFERENCES as $table => $colonne) {
                if (Schema::hasTable($table) && DB::table($table)->where($colonne, $ancien)->exists()) {
                    $encoreUtilise = true;
                    break;
                }
            }

            if (! $encoreUtilise) {
                DB::table('niveaux_config')->where('cle', $ancien)->delete();
            }
        }
    }

    public function down(): void
    {
        // Irréversible par nature : les plans supprimés n'ont plus de définition
        // à restaurer, et rebasculer les abonnements vers des plans disparus
        // serait pire que de ne rien faire. Le seeder reste la source si ces
        // niveaux devaient réapparaître.
    }
};
