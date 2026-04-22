<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('parametres_atelier', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->unique()->constrained('ateliers')->cascadeOnDelete();
            $table->char('langue', 2)->default('fr');
            $table->string('devise', 10)->default('XOF');
            $table->string('unite_mesure', 10)->default('cm');
            $table->enum('theme', ['clair', 'sombre'])->default('clair');
            $table->enum('mode_sync_photos', ['economique', 'equilibre', 'libre'])->default('libre');
            $table->boolean('multi_ateliers_actif')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('parametres_atelier'); }
};
