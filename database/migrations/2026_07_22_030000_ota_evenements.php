<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traçabilité des mises à jour OTA en conditions réelles.
 *
 * Jusqu'ici, savoir si une OTA arrivait sur les appareils demandait de le
 * tester à la main — ce qui est arrivé le 22/07 : la 1.0.143 échouait
 * silencieusement, et rien ne le disait avant un test manuel sur un appareil
 * branché. Un atelier dont la mise à jour échoue en continu ne le signale
 * jamais de lui-même.
 *
 * Chaque appareil rapporte l'ISSUE d'une tentative — succès ou échec — dès
 * que le bundle PRÉCÉDENT (encore actif, donc encore capable de parler au
 * serveur) détecte l'événement natif correspondant. Table dédiée plutôt
 * qu'une clé de `vitrine_settings` : c'est un journal qui grossit, pas un
 * réglage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gxt_ota_evenements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('atelier_id')->nullable();
            $table->string('app_id', 60);
            $table->string('version', 30);
            $table->enum('evenement', ['succes', 'echec_telechargement', 'echec_application']);
            $table->string('detail', 300)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['app_id', 'version', 'evenement']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gxt_ota_evenements');
    }
};
