<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MVP réseaux sociaux (direction, 20/07) — suivi des performances des
 * publications de NOS pages (lecture seule, API officielle Meta Graph —
 * jamais de scraping, jamais de publication automatique).
 *
 * Deux tables : le POST (identité stable, une ligne par publication) et ses
 * RELEVÉS (un instantané par collecte). L'historique des relevés permet de
 * voir la progression d'un post dans le temps — un simple « dernier chiffre »
 * ne dirait pas si un post a fait sa portée en 2 heures ou en 2 semaines.
 *
 * `plateforme` prévoit déjà instagram/linkedin (extensions annoncées) sans
 * migration supplémentaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reseaux_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('plateforme', 20);              // facebook | instagram | linkedin
            $table->string('externe_id', 100);             // id du post côté plateforme
            $table->timestamp('publie_at')->nullable();
            $table->string('format', 30)->nullable();      // photo | video | album | lien | texte
            $table->text('extrait')->nullable();           // début du message (repérage humain)
            $table->string('sujet', 100)->nullable();      // thème, saisi/édité en admin
            $table->string('permalink', 500)->nullable();
            $table->timestamps();
            $table->unique(['plateforme', 'externe_id']);
            $table->index('publie_at');
        });

        Schema::create('reseaux_stats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('post_id');
            $table->foreign('post_id')->references('id')->on('reseaux_posts')->cascadeOnDelete();
            $table->timestamp('releve_at');
            $table->unsignedInteger('portee')->default(0);        // reach unique
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('reactions')->default(0);
            $table->unsignedInteger('commentaires')->default(0);
            $table->unsignedInteger('partages')->default(0);
            $table->unsignedInteger('clics')->default(0);
            $table->index(['post_id', 'releve_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseaux_stats');
        Schema::dropIfExists('reseaux_posts');
    }
};
