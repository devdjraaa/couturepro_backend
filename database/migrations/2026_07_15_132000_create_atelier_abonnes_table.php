<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P173 : bouton « S'abonner / Enregistrer » — un visiteur peut suivre un créateur.
// Anonyme comme les likes → dé-doublonnage par clé visiteur.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atelier_abonnes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->string('visitor_key', 64);
            $table->timestamp('created_at')->nullable();

            $table->unique(['atelier_id', 'visitor_key']);
            $table->index('atelier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_abonnes');
    }
};
