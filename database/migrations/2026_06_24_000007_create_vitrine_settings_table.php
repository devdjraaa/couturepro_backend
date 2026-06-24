<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Réglages globaux de la vitrine (clé/valeur JSON) — flexible, éditable en admin.
    public function up(): void
    {
        Schema::create('vitrine_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cle')->unique();   // ex: 'banniere'
            $table->json('valeur')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitrine_settings');
    }
};
