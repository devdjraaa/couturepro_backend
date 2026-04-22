<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vetements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->nullable()->constrained('ateliers')->cascadeOnDelete();
            $table->string('nom', 150);
            $table->json('libelles_mesures');
            $table->tinyInteger('template_numero')->nullable();
            $table->boolean('is_systeme')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->uuid('created_by')->nullable();
            $table->enum('created_by_role', ['proprietaire', 'assistant'])->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['atelier_id', 'is_archived']);
        });
    }
    public function down(): void { Schema::dropIfExists('vetements'); }
};
