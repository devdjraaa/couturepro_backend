<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P204 : candidatures « Devenir partenaire » (formulaire modale).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatures_partenaires', function (Blueprint $table) {
            $table->id();
            $table->string('nom_organisation');
            $table->string('pays_region')->nullable();
            $table->string('categorie_souhaitee')->nullable();
            $table->text('type_apport')->nullable();
            $table->string('contact_nom')->nullable();
            $table->string('contact_email');
            $table->string('contact_telephone')->nullable();
            $table->text('message')->nullable();
            $table->string('statut')->default('en_attente')->index(); // en_attente | validee | rejetee
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures_partenaires');
    }
};
