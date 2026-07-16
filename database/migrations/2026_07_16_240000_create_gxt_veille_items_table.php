<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Veille opportunités (n8n) : les résultats hebdo sont stockés ICI et consultés dans
// l'admin — l'e-mail du lundi devient une simple notification courte (boîte mail allégée).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gxt_veille_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('semaine');                       // lundi de la semaine de collecte
            $table->string('titre', 300);
            $table->string('lien', 600);
            $table->boolean('ia_selection')->default(false); // retenu par la mini-IA
            $table->unsignedInteger('ia_rang')->nullable();  // 1 = le plus pertinent
            $table->text('ia_raison')->nullable();           // pourquoi c'est intéressant
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['semaine', 'lien']);
            $table->index(['semaine', 'ia_selection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gxt_veille_items');
    }
};
