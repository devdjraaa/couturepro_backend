<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mesures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('vetement_id')->constrained('vetements');
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->json('champs');
            $table->uuid('created_by');
            $table->enum('created_by_role', ['proprietaire', 'assistant']);
            $table->timestamps();
            $table->unique(['client_id', 'vetement_id']);
            $table->index('atelier_id');
        });
    }
    public function down(): void { Schema::dropIfExists('mesures'); }
};
