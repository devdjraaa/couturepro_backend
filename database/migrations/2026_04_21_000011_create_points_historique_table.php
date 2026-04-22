<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('points_historique', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->enum('type', ['abonnement_activation','client_cree','commande_validee','reseau_social','note_store','conversion','bonus_admin']);
            $table->integer('points');
            $table->string('description', 255);
            $table->uuid('reference_id')->nullable();
            $table->timestamp('created_at');
            $table->index(['atelier_id', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('points_historique'); }
};
