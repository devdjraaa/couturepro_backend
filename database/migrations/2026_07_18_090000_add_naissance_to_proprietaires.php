<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pt 58 (spec événements dynamiques) : jour + mois de naissance du professionnel
// (année facultative). Alimente l'événement « anniversaire utilisateur » du système
// d'événements (pt 57), en 100 % local une fois la donnée présente.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->unsignedTinyInteger('naissance_jour')->nullable();  // 1-31
            $table->unsignedTinyInteger('naissance_mois')->nullable();  // 1-12
        });
    }

    public function down(): void
    {
        Schema::table('proprietaires', fn (Blueprint $t) => $t->dropColumn(['naissance_jour', 'naissance_mois']));
    }
};
