<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->string('numero');                       // ex : FAC-2026-001
            $table->enum('type', ['devis', 'facture', 'recu'])->default('facture');
            $table->string('statut', 30)->default('non_payee'); // non_payee | acompte | soldee
            $table->string('client_nom');
            $table->string('client_telephone')->nullable();
            $table->date('date_emission');
            $table->date('date_echeance')->nullable();
            $table->json('lignes');                         // [{description, quantite, prix_unitaire}]
            $table->string('mode_paiement', 30)->nullable();
            $table->string('gabarit', 30)->default('standard');
            $table->decimal('acompte', 12, 2)->default(0);
            $table->decimal('tva_taux', 5, 2)->default(0);  // 0 = non assujetti, 18 = assujetti TVA
            $table->string('code_tracage')->nullable();
            $table->text('notes')->nullable();

            // Normalisation DGI — soit PDF normalisé joint manuellement (intérim),
            // soit champs renvoyés par e-MECeF (étape B : intégration API).
            $table->string('dgi_pdf_path')->nullable();
            $table->string('emecef_code')->nullable();
            $table->string('emecef_qr_url')->nullable();
            $table->json('emecef_data')->nullable();
            $table->timestamp('normalisee_at')->nullable();

            $table->timestamps();
            $table->index(['atelier_id', 'type']);
            $table->unique(['atelier_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
