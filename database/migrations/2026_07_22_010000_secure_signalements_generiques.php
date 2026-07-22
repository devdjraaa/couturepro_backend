<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Signalements génériques (profil / création / avis) — même faille que les avis,
 * restée ouverte sur cette route-ci.
 *
 * Avant : `POST /vitrine/signaler` était PUBLIQUE, sans authentification, sans
 * limitation de débit et sans déduplication. Au 3ᵉ signalement, le serveur
 * appliquait une sanction AUTOMATIQUE :
 *   · profil   → l'atelier passait en « gele » (boutique entière hors ligne) ;
 *   · creation → le vêtement était archivé.
 * N'importe qui pouvait donc mettre la boutique d'un créateur hors ligne avec
 * TROIS requêtes HTTP, sans compte. Aucun atelier n'avait été gelé au moment du
 * correctif — la faille n'a pas été exploitée.
 *
 * Après : on applique le principe déjà retenu pour les avis le 19/07 — le
 * signalement alimente la file de modération, il ne sanctionne jamais tout seul.
 * `empreinte` rattache chaque signalement à son auteur (client connecté ou clé
 * de visiteur) et l'index unique empêche de compter plusieurs fois la même
 * personne sur la même cible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signalements', function (Blueprint $table) {
            $table->string('empreinte', 64)->nullable()->after('motif');
            $table->unique(['type', 'cible_id', 'empreinte'], 'signalements_cible_empreinte_unique');
        });
    }

    public function down(): void
    {
        Schema::table('signalements', function (Blueprint $table) {
            $table->dropUnique('signalements_cible_empreinte_unique');
            $table->dropColumn('empreinte');
        });
    }
};
