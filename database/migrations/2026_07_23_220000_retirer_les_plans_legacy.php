<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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

    public function up(): void
    {
        foreach (self::REMPLACEMENTS as $ancien => $actuel) {
            // Le plan de remplacement doit exister : sur un environnement où il
            // manque, on ne touche à rien plutôt que de créer des orphelins.
            $config = DB::table('niveaux_config')->where('cle', $actuel)->value('config');
            if ($config === null) {
                continue;
            }

            // Le cliché de configuration suit le nouveau plan : sans cela,
            // l'atelier garderait les quotas de l'ancien.
            DB::table('abonnements')->where('niveau_cle', $ancien)->update([
                'niveau_cle'      => $actuel,
                'config_snapshot' => $config,
                'updated_at'      => now(),
            ]);

            DB::table('transactions_abonnement')->where('niveau_cle', $ancien)
                ->update(['niveau_cle' => $actuel, 'updated_at' => now()]);
        }

        foreach (array_keys(self::REMPLACEMENTS) as $ancien) {
            // Garde-fou : on ne supprime que ce qui n'est plus référencé nulle
            // part. Mieux vaut laisser un plan mort qu'une base incohérente.
            $encoreUtilise = DB::table('abonnements')->where('niveau_cle', $ancien)->exists()
                || DB::table('transactions_abonnement')->where('niveau_cle', $ancien)->exists();

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
