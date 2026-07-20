<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pts 68-69 — Rétablir `libelles_mesures` sur les modèles.
 *
 * La colonne (liste des mesures attendues pour un type de vêtement : tour de
 * poitrine, longueur…) avait été SUPPRIMÉE par la refonte d'avril, mais le
 * modèle Eloquent la référence toujours et la fonctionnalité « éditer les
 * mesures pendant la commande » en dépend : sans elle, l'éditeur inline n'a
 * aucune source de données.
 *
 * Les mesures elles-mêmes restent rattachées AU CLIENT (arbitrage acté) —
 * cette colonne ne porte que la LISTE des libellés à proposer à la saisie
 * quand ce type de vêtement est choisi dans une commande.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vetements', 'libelles_mesures')) {
            Schema::table('vetements', function (Blueprint $table) {
                $table->json('libelles_mesures')->nullable()->after('nom');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vetements', 'libelles_mesures')) {
            Schema::table('vetements', function (Blueprint $table) {
                $table->dropColumn('libelles_mesures');
            });
        }
    }
};
