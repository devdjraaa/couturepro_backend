<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->unique()->constrained('ateliers')->cascadeOnDelete();
            $table->string('niveau_cle', 50);
            $table->enum('statut', ['actif', 'expire', 'en_pause', 'essai'])->default('actif');
            $table->integer('jours_restants')->default(0);
            $table->timestamp('timestamp_debut')->nullable();
            $table->timestamp('timestamp_expiration')->nullable();
            $table->boolean('bonus_actif')->default(false);
            $table->integer('bonus_jours_restants')->default(0);
            $table->string('bonus_niveau_cle', 50)->nullable();
            $table->timestamp('bonus_timestamp_debut')->nullable();
            $table->json('config_snapshot')->nullable();
            $table->timestamps();
            $table->foreign('niveau_cle')->references('cle')->on('niveaux_config');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};
