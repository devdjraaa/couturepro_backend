<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Point 101 — Module « Mes Réalisations » : publication modérée de photos par
// l'artisan/le designer. 4 statuts, certification d'auteur, consentement des
// personnes visibles, watermark à la publication, anti-abus (10 envois/semaine).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->text('description')->nullable();
            // [{ path, url, watermark_path?, watermark_url? }] — le watermark est
            // généré à la publication (pas à l'envoi).
            $table->json('images')->nullable();
            // brouillon | en_attente | publiee | refusee
            $table->string('statut')->default('brouillon');
            // Certification d'auteur obligatoire (bloquante) + consentement des
            // personnes visibles déclaré par l'artisan (double sécurité avec la modération).
            $table->boolean('certifie_auteur')->default(false);
            $table->boolean('consentement_personnes')->default(false);
            // Modération.
            $table->text('motif_refus')->nullable();
            $table->uuid('modere_par')->nullable();
            $table->timestamp('modere_at')->nullable();
            $table->timestamp('soumis_at')->nullable();
            $table->timestamp('publie_at')->nullable();
            $table->timestamps();

            $table->index(['atelier_id', 'statut']);
            $table->index('statut');
            $table->index('soumis_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisations');
    }
};
