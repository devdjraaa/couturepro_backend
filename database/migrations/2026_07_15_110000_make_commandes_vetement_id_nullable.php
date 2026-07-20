<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Fix sync : une commande peut ne pas avoir de vêtement principal (commande « description
// seule » ou multi-articles où les vêtements sont dans commande_items). La colonne
// vetement_id était NOT NULL → le push offline échouait (SQLSTATE 23502) pour ces commandes.
//
// Écrite au départ en SQL brut `ALTER COLUMN … DROP NOT NULL`, syntaxe que seul
// PostgreSQL comprend. La production tourne bien sous PostgreSQL, mais la migration
// échouait sur MariaDB — donc en local, où elle bloquait TOUTE la suite des migrations :
// d'où des tables absentes en développement et l'impossibilité de vérifier quoi que ce
// soit hors production. Le constructeur de schéma produit la bonne syntaxe pour chaque
// moteur et préserve la clé étrangère.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->uuid('vetement_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->uuid('vetement_id')->nullable(false)->change();
        });
    }
};
