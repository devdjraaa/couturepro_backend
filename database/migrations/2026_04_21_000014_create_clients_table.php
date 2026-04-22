<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('telephone', 25)->nullable();
            $table->enum('type_profil', ['homme', 'femme', 'enfant', 'mixte']);
            $table->string('avatar_key', 60)->nullable();
            $table->uuid('created_by');
            $table->enum('created_by_role', ['proprietaire', 'assistant']);
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->uuid('archived_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('atelier_id');
            $table->index(['atelier_id', 'is_archived']);
            $table->unique(['atelier_id', 'nom', 'prenom']);
        });
    }
    public function down(): void { Schema::dropIfExists('clients'); }
};
