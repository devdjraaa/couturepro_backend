<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tickets_support', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 30)->unique();
            $table->foreignUuid('atelier_id')->nullable()->constrained('ateliers')->nullOnDelete();
            $table->foreignUuid('proprietaire_id')->constrained('proprietaires');
            $table->enum('categorie', ['facturation', 'technique', 'compte', 'abonnement', 'autre']);
            $table->enum('priorite', ['faible', 'normale', 'haute', 'urgente'])->default('normale');
            $table->enum('statut', ['ouvert', 'en_cours', 'en_attente_client', 'resolu', 'ferme'])->default('ouvert');
            $table->string('sujet', 255);
            $table->foreignUuid('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('resolu_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('statut');
            $table->index('priorite');
            $table->index('assigned_to');
            $table->index('proprietaire_id');
        });
    }
    public function down(): void { Schema::dropIfExists('tickets_support'); }
};
