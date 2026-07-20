<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `fonctionnalites.ordre_affichage` était un `tinyInteger`, donc plafonné à 127.
 *
 * Le référentiel en assigne déjà jusqu'à 133 : la valeur DÉPASSE la capacité de
 * la colonne. Le défaut ne se voit pas en production, où PostgreSQL n'a pas de
 * tinyint et reçoit un `smallint` (32 767) ; il se manifeste partout ailleurs,
 * où l'insertion est rejetée. C'est ce qui bloquait les migrations en local, et
 * donc toute vérification hors production.
 *
 * Au-delà du dépannage : le référentiel s'allonge à chaque lot de
 * fonctionnalités, et un ordre d'affichage plafonné à 127 finira par être
 * atteint pour de bon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fonctionnalites', function (Blueprint $table) {
            $table->smallInteger('ordre_affichage')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('fonctionnalites', function (Blueprint $table) {
            $table->tinyInteger('ordre_affichage')->default(0)->change();
        });
    }
};
