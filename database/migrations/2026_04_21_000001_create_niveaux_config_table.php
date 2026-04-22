<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('niveaux_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('cle', 50)->unique();
            $table->string('label', 100);
            $table->smallInteger('duree_jours');
            $table->decimal('prix_xof', 10, 2)->default(0);
            $table->decimal('prix_mensuel_equivalent_xof', 10, 2)->nullable();
            $table->json('config');
            $table->boolean('is_actif')->default(true);
            $table->tinyInteger('ordre_affichage')->default(0);
            $table->string('description_courte', 255)->nullable();
            $table->timestamps();
            // updated_by ajouté en migration 023 après création de la table admins
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('niveaux_config');
    }
};
