<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * S08C-29 — Avis : publication automatique + signalement non destructif.
 *
 * Avant : un signalement anonyme (route publique, sans authentification ni
 * limitation) faisait passer un avis « valide » en « signale », ce qui le
 * retirait INSTANTANÉMENT de la vitrine, sans arbitrage ni retour possible.
 * N'importe qui pouvait donc faire disparaître les avis d'un créateur.
 *
 * Après : le signalement incrémente un compteur et horodate, mais ne change
 * plus le statut — l'avis reste visible tant qu'un administrateur n'a pas
 * tranché. Le signal est conservé pour alimenter la file de modération admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->unsignedInteger('signalements_count')->default(0)->after('statut');
            $table->timestamp('signale_at')->nullable()->after('signalements_count');
        });

        // Les avis étaient publiés uniquement après validation du créateur.
        // Cette validation est supprimée (le créateur était juge et partie) :
        // les avis en attente doivent donc être publiés, sinon ils resteraient
        // invisibles à vie faute de validateur.
        DB::table('avis')->where('statut', 'en_attente')->update(['statut' => 'valide']);

        // Aucun avis n'avait été dépublié par la faille au moment de la migration,
        // mais on remet en ligne d'éventuels signalements subis entre-temps :
        // le signal est conservé via le compteur, l'arbitrage revient à l'admin.
        DB::table('avis')->where('statut', 'signale')->update([
            'statut'             => 'valide',
            'signalements_count' => DB::raw('GREATEST(signalements_count, 1)'),
            'signale_at'         => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropColumn(['signalements_count', 'signale_at']);
        });
    }
};
