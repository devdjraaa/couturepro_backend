<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pt 24 — Notifications côté CLIENT.
 *
 * Le client final était déjà prévenu de l'avancée de sa commande… par e-mail
 * uniquement. Un e-mail se perd, part en indésirable, ou n'est simplement pas
 * relevé — et le client revient alors sur l'espace client sans rien y trouver
 * qui lui dise où en est sa commande. C'est exactement le moment où il écrit au
 * créateur pour demander.
 *
 * Table distincte de `notifications_systeme` : celle-ci appartient aux ateliers
 * (colonne `atelier_id`, permissions d'équipe, canal « info »). Y ajouter une
 * troisième population aurait rendu chaque requête conditionnelle au type de
 * destinataire — la source de bugs classique quand deux publics partagent une
 * table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications_client', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('gxt_client_id')->constrained('gxt_clients')->cascadeOnDelete();

            $table->string('type', 40);          // etape_commande, livraison, avis_publie…
            $table->string('titre', 150);
            $table->text('contenu')->nullable();
            $table->string('lien', 300)->nullable();

            // Référence libre vers l'objet concerné (commande, avis…). Sans
            // contrainte : la notification doit SURVIVRE à la suppression de
            // l'objet — « votre commande a été annulée » reste vraie et utile
            // même une fois la commande effacée.
            $table->string('sujet_type', 40)->nullable();
            $table->uuid('sujet_id')->nullable();

            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            // La liste est toujours lue « les non lues d'abord, puis par date ».
            $table->index(['gxt_client_id', 'lu_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_client');
    }
};
