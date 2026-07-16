<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// L'essai promet un « accès complet » (maquette officielle des plans, 16/07/2026) : les designers
// en essai recevaient le plan artisan `standard_mensuel` (pas de patrons, quotas artisan…).
// Rattrapage des comptes existants ; les nouveaux passent par NiveauConfig::cleEssaiPour().
return new class extends Migration
{
    public function up(): void
    {
        // Garde : sur un environnement en retard de migrations, on ne fait rien (rattrapage prod only).
        if (! Schema::hasColumn('ateliers', 'type') || ! Schema::hasColumn('proprietaires', 'type_atelier')) {
            return;
        }

        // 1) Sous-ateliers créés sans type : ils héritent du type du propriétaire.
        //    (sous-requête corrélée : portable MySQL/MariaDB + PostgreSQL)
        DB::statement("
            UPDATE ateliers
            SET type = (
                SELECT p.type_atelier FROM proprietaires p
                WHERE p.id = ateliers.proprietaire_id
            )
            WHERE ateliers.type IS NULL
              AND EXISTS (
                SELECT 1 FROM proprietaires p
                WHERE p.id = ateliers.proprietaire_id AND p.type_atelier IS NOT NULL
              )
        ");

        // 2) Essais de designers posés sur le plan artisan → niveau Studio (master_mensuel),
        //    avec snapshot rafraîchi sur la config du bon plan (même logique qu'à l'inscription).
        $master = DB::table('niveaux_config')->where('cle', 'master_mensuel')->first();

        DB::table('abonnements')
            ->where('statut', 'essai')
            ->where('niveau_cle', 'standard_mensuel')
            ->whereIn('atelier_id', fn ($q) => $q->select('id')->from('ateliers')->where('type', 'designer'))
            ->update([
                'niveau_cle'      => 'master_mensuel',
                'config_snapshot' => $master?->config,
                'updated_at'      => now(),
            ]);
    }

    public function down(): void
    {
        // Irréversible sans perte (on ne sait pas quels essais ont été migrés) — no-op assumé.
    }
};
