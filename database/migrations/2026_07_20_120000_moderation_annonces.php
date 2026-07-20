<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ANN-10 — Modération des annonces (décision direction du 20/07).
 *
 * Publication LIBRE : une annonce part en ligne immédiatement, sans validation
 * préalable (le designer ne doit pas attendre pour communiquer). La modération
 * intervient A POSTERIORI, uniquement si un contenu inapproprié est signalé.
 *
 * Même modèle que les avis : un signalement n'enlève RIEN tout seul, il
 * incrémente un compteur qui fait remonter l'annonce dans la file admin. Seul un
 * administrateur peut masquer — et le masquage est réversible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('annonces', function (Blueprint $table) {
            $table->unsignedInteger('signalements_count')->default(0);
            $table->timestamp('signale_at')->nullable();
            $table->timestamp('masquee_at')->nullable();
            $table->text('motif_masquage')->nullable();

            $table->index('masquee_at');
            $table->index('signalements_count');
        });
    }

    public function down(): void
    {
        Schema::table('annonces', function (Blueprint $table) {
            $table->dropIndex(['masquee_at']);
            $table->dropIndex(['signalements_count']);
            $table->dropColumn(['signalements_count', 'signale_at', 'masquee_at', 'motif_masquage']);
        });
    }
};
