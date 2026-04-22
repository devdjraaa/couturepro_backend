<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('offres_speciales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->foreignUuid('admin_id')->constrained('admins');
            $table->string('label', 150);
            $table->string('niveau_base_cle', 50);
            $table->json('config_override');
            $table->decimal('prix_special', 10, 2)->nullable();
            $table->smallInteger('duree_jours');
            $table->enum('statut', ['actif', 'expire', 'annule'])->default('actif');
            $table->timestamp('expire_at')->nullable();
            $table->text('notes_internes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('niveau_base_cle')->references('cle')->on('niveaux_config');
            $table->index(['atelier_id', 'statut']);
        });
    }
    public function down(): void { Schema::dropIfExists('offres_speciales'); }
};
