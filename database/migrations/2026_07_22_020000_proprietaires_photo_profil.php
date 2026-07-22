<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photo de profil du propriétaire.
 *
 * Les CLIENTS ont une photo depuis toujours (`clients.photo_url`), et le
 * composant Avatar sait l'afficher — mais le propriétaire, lui, n'avait AUCUNE
 * colonne pour en stocker une. Il ne voyait donc que ses initiales, sans aucun
 * moyen d'y remédier : la fonction n'existait tout simplement pas.
 *
 * On stocke le CHEMIN (comme `ateliers.logo_path`) et non l'URL : le disque
 * public peut changer de domaine, l'URL est reconstruite à la lecture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
