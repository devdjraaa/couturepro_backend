<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quotas_mensuels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->smallInteger('annee');
            $table->tinyInteger('mois');
            $table->smallInteger('nb_clients_crees')->default(0);
            $table->smallInteger('nb_commandes_creees')->default(0);
            $table->smallInteger('nb_photos_vip')->default(0);
            $table->smallInteger('nb_factures_envoyees')->default(0);
            $table->timestamps();
            $table->unique(['atelier_id', 'annee', 'mois']);
        });
    }
    public function down(): void { Schema::dropIfExists('quotas_mensuels'); }
};
