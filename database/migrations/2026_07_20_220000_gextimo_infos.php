<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CLI-2 — « Gextimo Infos ».
 *
 * Un onglet distinct des notifications. La séparation n'est pas cosmétique :
 * une notification dit ce qui est arrivé à VOTRE atelier (commande créée,
 * abonnement qui expire) et appelle une action ; une info est un message
 * éditorial de Gextimo vers la communauté (nouveauté, astuce, formation). Les
 * mélanger, c'est noyer les alertes qui comptent sous des annonces, ou faire
 * disparaître une annonce sous vingt notifications de commandes.
 *
 * On ÉTEND `notifications_systeme` plutôt que de créer une table jumelle :
 * même cycle de vie, même écran de diffusion admin, et le jour où l'on veut
 * qu'une info devienne une notification ciblée, c'est un changement de canal.
 *
 * Deux ajouts structurants :
 *
 * 1. `cible` — à qui s'adresse le message. La diffusion existante était tout ou
 *    rien (`atelier_id` renseigné = une personne, null = tout le monde). Une
 *    annonce « nouveauté réservée aux comptes Designer » n'était pas exprimable.
 *
 * 2. `infos_lectures` — l'état de lecture PAR atelier. Défaut réel du socle
 *    existant : `is_read` est porté par la ligne diffusée, donc le premier
 *    atelier qui lisait une annonce la marquait lue POUR TOUT LE MONDE. Le
 *    problème ne se voyait pas tant que la diffusion générale n'était pas
 *    utilisée ; « Gextimo Infos » repose entièrement dessus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications_systeme', function (Blueprint $table) {
            // 'notification' = l'existant, inchangé ; 'info' = le nouvel onglet.
            // Défaut sur l'existant : aucune ligne déjà en base ne change de sens.
            $table->string('canal', 20)->default('notification')->index();
            $table->string('categorie', 30)->nullable();
            $table->json('cible')->nullable();
            $table->boolean('epingle')->default(false);
            $table->timestamp('publie_at')->nullable()->index();
            $table->timestamp('expire_at')->nullable();
        });

        Schema::create('infos_lectures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('notification_id')->constrained('notifications_systeme')->cascadeOnDelete();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->timestamp('lu_at');
            $table->timestamps();

            // Un atelier ne lit une info qu'une fois : la contrainte évite les
            // doublons si l'écran renvoie le marquage plusieurs fois.
            $table->unique(['notification_id', 'atelier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('infos_lectures');

        Schema::table('notifications_systeme', function (Blueprint $table) {
            $table->dropColumn(['canal', 'categorie', 'cible', 'epingle', 'publie_at', 'expire_at']);
        });
    }
};
