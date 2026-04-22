<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('photos_vip', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->uuid('uploaded_by'); // proprietaire_id
            $table->string('file_path', 500);
            $table->string('file_url', 500)->nullable();
            $table->string('nom', 150)->nullable();
            $table->bigInteger('taille_octets');
            $table->timestamps();
            $table->softDeletes();
            $table->index('atelier_id');
        });
    }
    public function down(): void { Schema::dropIfExists('photos_vip'); }
};
