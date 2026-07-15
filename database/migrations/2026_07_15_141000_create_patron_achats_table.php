<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P162-163 : achat d'un patron. Le code de transaction (unique) est LA clé de
// récupération : il est affiché sur le reçu et suffit pour re-télécharger le contenu
// après paiement (même si la session a été fermée). Menu « Récupérer ma commande ».
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patron_achats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('patron_id')->constrained('patrons')->cascadeOnDelete();
            $table->foreignUuid('paiement_id')->nullable()->constrained('paiements')->nullOnDelete();
            $table->string('code_transaction', 24)->unique();     // reçu / clé de récupération
            $table->string('acheteur_nom');
            $table->string('acheteur_email')->nullable();
            $table->string('acheteur_tel')->nullable();
            $table->unsignedInteger('montant');                    // XOF payé (snapshot du prix)
            $table->string('statut', 20)->default('pending');      // pending | paye | echoue
            $table->unsignedInteger('nb_telechargements')->default(0);
            $table->timestamp('paye_at')->nullable();
            $table->timestamps();

            $table->index('patron_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patron_achats');
    }
};
