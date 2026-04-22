<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('notifications_systeme', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->nullable()->constrained('ateliers')->cascadeOnDelete();
            $table->string('titre', 255);
            $table->text('contenu');
            $table->enum('type', ['promo','mise_a_jour','alerte_sync','alerte_abonnement','info']);
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->index(['atelier_id', 'is_read']);
        });
    }
    public function down(): void { Schema::dropIfExists('notifications_systeme'); }
};
