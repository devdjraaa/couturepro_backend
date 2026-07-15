<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P159-160 : « J'aime » public sur chaque création (vêtement publié en vitrine).
// N'importe quel visiteur peut liker SANS être inscrit → on dé-doublonne par une clé
// visiteur (UUID généré côté client, stocké en localStorage), pas par compte.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creation_likes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vetement_id')->constrained('vetements')->cascadeOnDelete();
            $table->string('visitor_key', 64);          // empreinte visiteur anonyme
            $table->timestamp('created_at')->nullable();

            $table->unique(['vetement_id', 'visitor_key']); // un like par visiteur et par création
            $table->index('vetement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creation_likes');
    }
};
