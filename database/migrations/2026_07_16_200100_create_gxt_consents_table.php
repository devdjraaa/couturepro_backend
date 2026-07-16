<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P202 / Spec Espace Client v3 — Phase 1.
// Consentements APDP tracés (cookies / marketing / analytics / personnalisation).
// Sert d'INTERRUPTEUR : GA4 / Meta Pixel / Clarity ne se chargent qu'après accord.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gxt_consents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('gxt_clients')->cascadeOnDelete();
            $table->boolean('cookie_consent')->default(false);
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('analytics_consent')->default(false);
            $table->boolean('personalization_consent')->default(false);
            $table->string('version_politique', 10)->nullable();
            $table->string('ip_hash', 100)->nullable(); // IP hashée (jamais en clair — APDP)
            $table->timestamps();

            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gxt_consents');
    }
};
