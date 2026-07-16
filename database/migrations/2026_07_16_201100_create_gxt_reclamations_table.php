<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P202 / Espace Client v3 — Phase 2 : réclamations client sur une commande.
// Fil de discussion tracé et horodaté jusqu'à résolution (client / designer / admin).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gxt_reclamations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gxt_client_id')->constrained('gxt_clients')->cascadeOnDelete();
            $table->foreignUuid('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->string('sujet', 150);
            $table->string('statut', 20)->default('ouverte'); // ouverte | en_traitement | resolue
            $table->timestamp('resolue_at')->nullable();
            $table->timestamps();

            $table->index(['atelier_id', 'statut']);
        });

        Schema::create('gxt_reclamation_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reclamation_id')->constrained('gxt_reclamations')->cascadeOnDelete();
            $table->string('auteur_type', 20); // client | designer | admin
            $table->uuid('auteur_id')->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gxt_reclamation_messages');
        Schema::dropIfExists('gxt_reclamations');
    }
};
