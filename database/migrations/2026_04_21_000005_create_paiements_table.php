<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->string('niveau_cle', 50);
            $table->smallInteger('duree_jours');
            $table->decimal('montant', 10, 2);
            $table->string('devise', 10)->default('XOF');
            $table->string('provider', 50);
            $table->string('provider_transaction_id', 255)->nullable();
            $table->json('provider_metadata')->nullable();
            $table->enum('statut', ['pending', 'completed', 'failed', 'refunded', 'expired'])->default('pending');
            $table->string('checkout_url', 500)->nullable();
            $table->timestamp('initiated_at');
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->foreign('niveau_cle')->references('cle')->on('niveaux_config');
            $table->index('atelier_id');
            $table->index('statut');
            $table->index(['provider', 'provider_transaction_id']);
            $table->index('expires_at');
            // validated_by (FK admins) ajouté en migration 024
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
