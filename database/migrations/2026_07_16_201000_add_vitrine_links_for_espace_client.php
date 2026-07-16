<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P202 / Espace Client v3 — Phase 2 (approche « direct » validée par la direction) :
// la commande passée par un client vitrine (gxt_clients) atterrit DIRECTEMENT dans
// l'outil de production du designer (tables clients + commandes existantes).
// On ne duplique aucun système : on relie.
return new class extends Migration
{
    public function up(): void
    {
        // Fiche client d'atelier ↔ compte client vitrine (une fiche par atelier).
        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('gxt_client_id')->nullable()->index();
        });

        // Provenance de la commande + lien direct vers le client vitrine (notifications).
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('source', 20)->default('atelier'); // atelier | vitrine
            $table->uuid('gxt_client_id')->nullable()->index();
        });

        // Avis : relié au client vitrine et à la commande livrée (au lieu d'un simple nom libre).
        Schema::table('avis', function (Blueprint $table) {
            $table->uuid('gxt_client_id')->nullable()->index();
            $table->uuid('commande_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('clients', fn (Blueprint $t) => $t->dropColumn('gxt_client_id'));
        Schema::table('commandes', fn (Blueprint $t) => $t->dropColumn(['source', 'gxt_client_id']));
        Schema::table('avis', fn (Blueprint $t) => $t->dropColumn(['gxt_client_id', 'commande_id']));
    }
};
