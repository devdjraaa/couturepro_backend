<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ABO-1/5/8 — L'abonnement à un créateur devient rattaché à un COMPTE.
 *
 * Avant : abonnement 100 % anonyme (clé visiteur stockée dans le navigateur).
 * Conséquences : vider son cache faisait perdre tous ses abonnements, aucune
 * notification n'était possible, et le compteur d'abonnés n'était pas fiable.
 *
 * Après : `gxt_client_id` rattache l'abonnement au client de l'espace vitrine,
 * avec un consentement notifications SÉPARÉ de l'abonnement (exigence APDP /
 * Code du numérique béninois) et un statut pour la traçabilité.
 *
 * ⚠️ Les lignes anonymes existantes sont CONSERVÉES telles quelles : leur sort
 * (rattachement ou remise à zéro) est une décision direction en attente (ABO-9).
 * `visitor_key` devient donc nullable au lieu d'être supprimée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atelier_abonnes', function (Blueprint $table) {
            $table->foreignUuid('gxt_client_id')->nullable()->after('atelier_id')
                ->constrained('gxt_clients')->cascadeOnDelete();

            // Consentement notifications : distinct de l'abonnement lui-même.
            $table->boolean('notifications_optin')->default(false)->after('visitor_key');

            // Traçabilité : un désabonnement conserve la ligne (statistiques).
            $table->boolean('actif')->default(true)->after('notifications_optin');
            $table->timestamp('desabonne_at')->nullable()->after('actif');
            $table->timestamp('updated_at')->nullable();

            $table->unique(['atelier_id', 'gxt_client_id']);
            $table->index(['gxt_client_id', 'actif']);
        });

        // `visitor_key` n'est plus obligatoire (les nouveaux abonnements passent par un compte).
        Schema::table('atelier_abonnes', function (Blueprint $table) {
            $table->string('visitor_key', 64)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('atelier_abonnes', function (Blueprint $table) {
            $table->dropUnique(['atelier_id', 'gxt_client_id']);
            $table->dropIndex(['gxt_client_id', 'actif']);
            $table->dropConstrainedForeignId('gxt_client_id');
            $table->dropColumn(['notifications_optin', 'actif', 'desabonne_at', 'updated_at']);
        });
    }
};
