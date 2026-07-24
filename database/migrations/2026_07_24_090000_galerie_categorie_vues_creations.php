<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Galerie publique — catégorie et compteur de vues par création.
 *
 * Deux manques signalés par la direction :
 *  - la galerie de l'accueil renvoyait « categorie => null » en dur, donc les
 *    filtres par catégorie ne faisaient rien sur les vraies données ;
 *  - aucune trace des VUES : on ne savait pas combien de fois une création
 *    avait été regardée (les likes existaient déjà via `creation_likes`, pas
 *    les vues).
 *
 * La catégorie est une simple clé (« robes », « traditionnel »…) rattachée à la
 * taxonomie éditable des réglages vitrine — pas une valeur en dur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->string('categorie', 40)->nullable()->after('publie_vitrine');
            // Compteur dénormalisé : la galerie publique se lit très souvent,
            // recompter les vues à chaque affichage serait coûteux. L'incrément
            // est dédupliqué par session (fenêtre de 30 min) pour ne pas gonfler.
            $table->unsignedInteger('vues')->default(0)->after('categorie');
            $table->index('categorie');
        });
    }

    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropIndex(['categorie']);
            $table->dropColumn(['categorie', 'vues']);
        });
    }
};
