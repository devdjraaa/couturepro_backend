<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PL-4 : liste d'attente clients (Studio). Prospects en attente d'une place /
// d'un créneau de production, avec statut de suivi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liste_attente', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('nom');
            $table->string('telephone')->nullable();
            $table->text('note')->nullable();
            $table->string('statut')->default('en_attente'); // en_attente | contacte | converti | annule
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['atelier_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('liste_attente');
    }
};
