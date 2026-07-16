<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P202 / Espace Client v3 — Phase 3 : source de vérité du comportement client.
// SEULS les événements MÉTIER sont stockés ici (vue produit, panier, wishlist, achat,
// recherche, avis, réclamation…). Les micro-événements (scroll, temps de page) vont
// dans GA4 uniquement — c'est le choix d'architecture v3 (base légère, GA4 = firehose).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gxt_evenements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('gxt_client_id')->nullable();  // null = visiteur anonyme (session_id)
            $table->string('session_id', 100);
            $table->string('type', 50);
            $table->string('article_type', 30)->nullable();  // vetement | creation | patron
            $table->string('article_id', 50)->nullable();    // uuid (vetements) OU id numérique (creations/patrons)
            $table->uuid('atelier_id')->nullable();          // designer concerné
            $table->uuid('commande_id')->nullable();
            $table->decimal('valeur_fcfa', 12, 2)->nullable();
            $table->integer('duree_secondes')->nullable();
            $table->json('metadata')->nullable();            // terme de recherche, filtres, élément cliqué…
            $table->string('appareil', 20)->nullable();      // mobile | desktop | tablette
            $table->timestamp('created_at')->useCurrent();   // événements immuables : pas d'updated_at

            $table->index(['type', 'created_at']);
            $table->index(['gxt_client_id', 'created_at']);
            $table->index(['atelier_id', 'created_at']);
            $table->index('session_id');
        });

        // Termes cherchés sans résultat (compteur agrégé) → opportunités produit pour les designers.
        Schema::create('gxt_recherches_sans_resultat', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('terme', 100)->unique();
            $table->unsignedInteger('nombre_fois')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gxt_recherches_sans_resultat');
        Schema::dropIfExists('gxt_evenements');
    }
};
