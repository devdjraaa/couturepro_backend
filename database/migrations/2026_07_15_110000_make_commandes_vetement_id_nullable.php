<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Fix sync : une commande peut ne pas avoir de vêtement principal (commande « description
// seule » ou multi-articles où les vêtements sont dans commande_items). La colonne
// vetement_id était NOT NULL → le push offline échouait (SQLSTATE 23502) pour ces commandes.
// SQL brut : on retire seulement le NOT NULL, la clé étrangère est préservée.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE commandes ALTER COLUMN vetement_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE commandes ALTER COLUMN vetement_id SET NOT NULL');
    }
};
