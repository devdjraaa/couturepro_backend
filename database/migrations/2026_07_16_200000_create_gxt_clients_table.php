<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P202 / Spec Espace Client v3 — Phase 1.
// Client FINAL de la vitrine (personne qui parcourt les designers et commande).
// DISTINCT de `clients` (clients d'atelier, mesures) : préfixe `gxt_`, PK UUID (convention du projet).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gxt_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom', 100)->nullable();
            $table->string('prenom', 100)->nullable();
            $table->string('email', 150)->unique();
            $table->string('telephone_whatsapp', 25)->nullable();
            $table->string('google_id', 100)->nullable();
            // Attribution / acquisition (UTM + referrer capturés à la 1re visite).
            $table->string('utm_source', 60)->nullable();
            $table->string('utm_medium', 60)->nullable();
            $table->string('utm_campaign', 120)->nullable();
            $table->string('referrer_url', 255)->nullable();
            // Contexte technique (parsé du User-Agent ou fourni par le front).
            $table->string('appareil', 60)->nullable();
            $table->string('systeme_os', 60)->nullable();
            $table->string('navigateur', 60)->nullable();
            $table->string('pays', 60)->nullable();
            $table->string('ville', 60)->nullable();
            $table->string('langue', 10)->nullable();
            $table->timestamp('derniere_connexion_at')->nullable();
            $table->timestamps();

            $table->index('google_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gxt_clients');
    }
};
