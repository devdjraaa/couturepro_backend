<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P204 : partenaires affichés sur la vitrine (par catégorie + bandeau accueil).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('categorie')->index();       // champ libre (catégories évolutives)
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('site_url')->nullable();
            $table->string('pays')->nullable();
            $table->boolean('actif')->default(true)->index();
            $table->boolean('is_cle')->default(false);   // mis en avant dans le bandeau d'accueil
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partenaires');
    }
};
