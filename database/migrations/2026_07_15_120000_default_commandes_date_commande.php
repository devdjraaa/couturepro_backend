<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Fix sync (critique) : les commandes créées offline (WatermelonDB) n'ont pas de colonne
// date_commande dans leur schéma → le push offline insérait NULL → NOT NULL violation
// (SQLSTATE 23502) qui bloquait TOUTE la file de synchronisation. On ajoute un DEFAULT
// CURRENT_DATE : les inserts qui omettent la colonne prennent la date du jour.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE commandes ALTER COLUMN date_commande SET DEFAULT CURRENT_DATE');
        // Filet : d'éventuelles lignes déjà à NULL (imports) reçoivent aujourd'hui.
        DB::statement('UPDATE commandes SET date_commande = CURRENT_DATE WHERE date_commande IS NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE commandes ALTER COLUMN date_commande DROP DEFAULT');
    }
};
