<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creations_designer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('categorie'); // croquis | fiche_technique | patron | moodboard
            $table->string('titre');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('public')->default(false);
            $table->timestamps();

            $table->index(['atelier_id', 'categorie']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creations_designer');
    }
};
