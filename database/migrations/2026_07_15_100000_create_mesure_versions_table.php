<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P74 : historique versionné des séries de mesures d'une cliente
// (date, atelier, qui les a prises, numéro de version). Append-only.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesure_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('mesure_id')->constrained('mesures')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('champs');
            $table->uuid('created_by')->nullable();
            $table->enum('created_by_role', ['proprietaire', 'assistant'])->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['mesure_id', 'version']);
            $table->index(['client_id', 'atelier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesure_versions');
    }
};
