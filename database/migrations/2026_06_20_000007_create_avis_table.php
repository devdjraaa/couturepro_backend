<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('atelier_id')->index();
            $table->string('auteur_nom');
            $table->unsignedTinyInteger('note');             // 1..5
            $table->text('texte')->nullable();
            $table->string('statut')->default('en_attente'); // en_attente | valide | rejete
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
