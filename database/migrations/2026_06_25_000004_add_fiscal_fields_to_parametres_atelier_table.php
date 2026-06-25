<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametres_atelier', function (Blueprint $table) {
            $table->boolean('assujetti_tva')->default(false);
            // Jeton/identifiant e-MECeF de l'atelier (utilisé à l'étape B pour la
            // normalisation DGI). Stocké chiffré côté modèle.
            $table->text('emecef_token')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parametres_atelier', function (Blueprint $table) {
            $table->dropColumn(['assujetti_tva', 'emecef_token']);
        });
    }
};
