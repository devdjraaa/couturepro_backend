<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions_abonnement', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code_transaction', 100)->unique();
            $table->foreignUuid('atelier_id')->nullable()->constrained('ateliers')->nullOnDelete();
            $table->foreignUuid('paiement_id')->nullable()->constrained('paiements')->nullOnDelete();
            $table->string('niveau_cle', 50);
            $table->smallInteger('duree_jours');
            $table->decimal('montant', 10, 2);
            $table->string('devise', 10)->default('XOF');
            $table->enum('canal', ['webhook', 'manuel'])->default('manuel');
            $table->enum('statut', ['disponible', 'utilise', 'annule'])->default('disponible');
            $table->timestamp('utilise_at')->nullable();
            // created_by (FK admins) ajouté ici mais admins n'existe pas encore
            // => on l'ajoute directement car la migration admins (022) vient après
            // Solution : nullable sans FK ici, FK ajoutée en migration 024
            $table->uuid('created_by')->nullable(); // Admin ID (nullable si canal webhook)
            $table->timestamps();
            $table->foreign('niveau_cle')->references('cle')->on('niveaux_config');
            $table->index('statut');
            $table->index('atelier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions_abonnement');
    }
};
