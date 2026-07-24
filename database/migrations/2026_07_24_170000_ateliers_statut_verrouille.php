<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ajoute « verrouille » aux statuts autorisés d'un atelier.
 *
 * Toute la fonctionnalité de verrouillage des sous-ateliers excédentaires
 * (baisse de plan → l'atelier que le plan ne couvre plus est verrouillé, puis
 * rouvert via « Déverrouiller ») s'appuie sur ce statut, mais la contrainte
 * CHECK ne l'autorisait pas : sur PostgreSQL (prod) l'écriture partait en 500.
 * Statuts historiques : actif / expire / essai / gele.
 */
return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ateliers DROP CONSTRAINT IF EXISTS ateliers_statut_check');
            DB::statement("ALTER TABLE ateliers ADD CONSTRAINT ateliers_statut_check CHECK (statut IN ('actif','expire','essai','gele','verrouille'))");
        } else {
            DB::statement("ALTER TABLE ateliers MODIFY COLUMN statut ENUM('actif','expire','essai','gele','verrouille') NOT NULL DEFAULT 'essai'");
        }
    }

    public function down(): void
    {
        // On remet d'abord les ateliers verrouillés en « gele » pour ne pas
        // violer la contrainte réduite.
        DB::table('ateliers')->where('statut', 'verrouille')->update(['statut' => 'gele']);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE ateliers DROP CONSTRAINT IF EXISTS ateliers_statut_check');
            DB::statement("ALTER TABLE ateliers ADD CONSTRAINT ateliers_statut_check CHECK (statut IN ('actif','expire','essai','gele'))");
        } else {
            DB::statement("ALTER TABLE ateliers MODIFY COLUMN statut ENUM('actif','expire','essai','gele') NOT NULL DEFAULT 'essai'");
        }
    }
};
