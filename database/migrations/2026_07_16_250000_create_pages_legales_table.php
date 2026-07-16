<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pages légales du footer vitrine éditables depuis le back-office (éditeur riche).
// Tant qu'une page n'a pas de contenu en base, la vitrine affiche le texte i18n
// historique (fallback) : migration en douceur, rien ne casse.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages_legales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cle', 60)->unique(); // confidentialite | mentions | cookies | cgu | …
            $table->string('titre_fr', 200)->nullable();
            $table->string('titre_en', 200)->nullable();
            $table->longText('contenu_fr')->nullable(); // HTML produit par l'éditeur admin
            $table->longText('contenu_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages_legales');
    }
};
