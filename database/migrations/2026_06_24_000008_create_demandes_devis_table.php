<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_devis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('atelier_id')->index();
            $table->uuid('vetement_id')->nullable(); // création concernée (optionnel)
            $table->string('nom');
            $table->string('contact');               // tel / WhatsApp / e-mail
            $table->text('description');
            $table->string('budget')->nullable();
            $table->string('delai')->nullable();
            $table->string('statut')->default('nouveau'); // nouveau | traite
            $table->timestamps();
            $table->index(['atelier_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_devis');
    }
};
