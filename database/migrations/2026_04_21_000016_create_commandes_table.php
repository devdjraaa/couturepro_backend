<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('commandes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignUuid('vetement_id')->constrained('vetements');
            $table->uuid('created_by');
            $table->enum('created_by_role', ['proprietaire', 'assistant']);
            $table->tinyInteger('quantite')->unsigned()->default(1);
            $table->decimal('prix', 12, 2)->nullable();
            $table->decimal('acompte', 12, 2)->nullable();
            $table->enum('statut', ['en_cours', 'livre', 'annule'])->default('en_cours');
            $table->date('date_commande');
            $table->date('date_livraison_prevue')->nullable();
            $table->timestamp('date_livraison_effective')->nullable();
            $table->text('note_interne')->nullable();
            $table->boolean('rappel_j2_envoye')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['atelier_id', 'statut']);
            $table->index(['atelier_id', 'date_livraison_prevue']);
            $table->index('client_id');
        });
    }
    public function down(): void { Schema::dropIfExists('commandes'); }
};
