<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ANN-1 — Module « Annonces » (Espace Designer).
 *
 * Remplace l'« annonce de collection » minimale (2 colonnes greffées sur
 * `collections`, message seul, publication immédiate, et surtout une annonce qui
 * ÉCRASAIT la précédente — donc aucun historique possible).
 *
 * Publication gratuite, durée 1 à 10 jours choisie par le designer (la date de fin
 * est calculée), une seule annonce par jour et par atelier, historique conservé.
 * Le Boost (mise en avant payante) est porté par les colonnes `boost_*`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annonces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained()->cascadeOnDelete();

            $table->string('titre', 120);
            $table->text('message');
            // Bannière facultative : sans image, le message s'affiche seul (centré).
            $table->string('image_path', 500)->nullable();
            $table->string('image_url', 500)->nullable();

            // Le designer choisit une date de début + un nombre de jours ;
            // la date de fin est calculée par le serveur (jamais saisie).
            $table->date('date_debut');
            $table->unsignedTinyInteger('duree_jours');
            $table->date('date_fin');

            // Boost : mise en avant payante (1 / 3 / 7 jours), diffusion accrue.
            $table->boolean('boost_actif')->default(false);
            $table->date('boost_debut')->nullable();
            $table->unsignedTinyInteger('boost_duree_jours')->nullable();
            $table->date('boost_fin')->nullable();
            $table->unsignedInteger('boost_prix_xof')->nullable();
            $table->timestamp('boost_paye_at')->nullable();

            $table->timestamps();

            $table->index(['atelier_id', 'created_at']);
            $table->index(['date_debut', 'date_fin']);
            $table->index('boost_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annonces');
    }
};
