<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');                            // profil | creation | avis
            $table->uuid('cible_id')->index();                 // atelier_id | vetement_id | avis_id
            $table->string('motif')->nullable();
            $table->string('statut')->default('en_attente');   // en_attente | traite
            $table->timestamps();
            $table->index(['type', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
