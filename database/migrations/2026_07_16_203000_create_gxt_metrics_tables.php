<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P202 / Espace Client v3 — Phase 4 : tables de SYNTHÈSE (recalculées la nuit par
// `gxt:recalculer-metrics`). Choix v3 : 2 tables agrégées au lieu de 8 tables de scoring
// écrites en continu — la source de vérité reste gxt_evenements + commandes/avis.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gxt_client_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gxt_client_id')->unique()->constrained('gxt_clients')->cascadeOnDelete();
            $table->integer('engagement_score')->default(0);
            $table->string('segment', 10)->default('froid');   // froid | tiede | chaud | vip
            $table->json('interest_scores')->nullable();        // {categorie: score}
            $table->json('rfm')->nullable();                    // {r,f,m,segment}
            $table->json('clv')->nullable();                    // {montant_total, nb_commandes, frequence_jours, valeur_vie}
            $table->json('preferences')->nullable();            // {categories_top3, gamme_prix, designers_favoris}
            $table->string('risque_churn', 10)->default('faible'); // faible | moyen | eleve
            $table->timestamps();
        });

        Schema::create('gxt_designer_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->unique()->constrained('ateliers')->cascadeOnDelete();
            $table->integer('score_confiance')->default(0);     // /100 — mise en avant si > 80, alerte si < 50
            $table->json('confiance_details')->nullable();      // {livraison_temps, reclamation, note, delai, annulation}
            $table->json('revenus')->nullable();                // {mois_en_cours, mois_precedent, prediction, croissance}
            $table->json('articles_top')->nullable();           // top articles vus/commandés
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gxt_designer_metrics');
        Schema::dropIfExists('gxt_client_metrics');
    }
};
