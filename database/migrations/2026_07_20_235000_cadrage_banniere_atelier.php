<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * VIT-3 — Cadrage de la bannière « Ma vitrine ».
 *
 * L'écran de recadrage interactif (déplacement dans le cadre, aperçu temps
 * réel) revient à Aquilas, mais il ne peut rien construire tant que le serveur
 * ne sait pas STOCKER le résultat : aujourd'hui seul `banniere_path` existe,
 * et l'image est affichée centrée d'office.
 *
 * Le cadrage est enregistré en FRACTIONS de l'image (0 → 1), pas en pixels.
 * Deux raisons : la même bannière est servie à des tailles différentes selon
 * l'écran, et un cadrage en pixels deviendrait faux dès qu'on génère une
 * miniature. Des fractions restent justes à toutes les tailles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            // { x, y, largeur, hauteur } en fractions. Null = pas de cadrage
            // choisi : l'affichage reste centré, comportement actuel.
            $table->json('banniere_cadrage')->nullable()->after('banniere_type');
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn('banniere_cadrage');
        });
    }
};
