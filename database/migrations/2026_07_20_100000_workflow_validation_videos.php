<?php

use App\Models\NiveauConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * VID-3/4/5 — Workflow de validation des vidéos + quotas de correction.
 *
 * Avant : une vidéo était publiée IMMÉDIATEMENT, sans aucune modération, et ne
 * pouvait pas être modifiée (le contrôleur n'exposait que créer/supprimer) — ce
 * qui rendait le « compteur de corrections mensuelles » demandé impossible.
 *
 * Après : soumission → « en attente » → validation ou refus sous 24 h. Un refus
 * restitue le quota (les vidéos refusées ne comptent plus dans la limite du plan).
 * Les corrections et suppressions sont journalisées pour être plafonnées par mois
 * selon la formule : Gratuit 0 · Atelier 1 · Studio 2.
 */
return new class extends Migration
{
    private function correctionsPour(string $cle): int
    {
        return match (true) {
            str_starts_with($cle, 'master_')  => 2,   // Studio
            str_starts_with($cle, 'atelier_') => 1,   // Atelier
            default                           => 0,   // Gratuit : remplacement automatique
        };
    }

    public function up(): void
    {
        Schema::table('atelier_videos', function (Blueprint $table) {
            // en_attente | publiee | refusee
            $table->string('statut', 20)->default('en_attente')->after('url');
            // youtube | fichier
            $table->string('source', 20)->default('youtube')->after('statut');
            $table->string('fichier_path', 500)->nullable()->after('source');
            $table->text('motif_refus')->nullable();
            $table->timestamp('soumis_at')->nullable();
            $table->timestamp('modere_at')->nullable();
            $table->uuid('modere_par')->nullable();

            $table->index(['atelier_id', 'statut']);
            $table->index('soumis_at');
        });

        // Les vidéos déjà en ligne restent publiées : on ne dépublie personne.
        DB::table('atelier_videos')->update([
            'statut'    => 'publiee',
            'soumis_at' => DB::raw('created_at'),
        ]);

        // Journal des corrections, pour plafonner par mois (une suppression efface
        // la ligne vidéo : impossible de compter sans journal dédié).
        Schema::create('atelier_video_corrections', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);           // modification | suppression
            $table->timestamp('created_at')->nullable();

            $table->index(['atelier_id', 'created_at']);
        });

        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];
            $config['max_corrections_videos_mois'] = $this->correctionsPour($plan->cle);
            $plan->update(['config' => $config]);
        }

        if (! DB::table('fonctionnalites')->where('cle', 'max_corrections_videos_mois')->exists()) {
            DB::table('fonctionnalites')->insert([
                'cle'             => 'max_corrections_videos_mois',
                'label'           => 'Corrections de vidéos par mois',
                'description'     => 'Modifications ou suppressions de vidéos autorisées chaque mois',
                'type'            => 'numerique',
                'unite'           => 'corrections/mois',
                'categorie'       => 'communication',
                'is_actif'        => true,
                'ordre_affichage' => 132,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_video_corrections');

        Schema::table('atelier_videos', function (Blueprint $table) {
            $table->dropIndex(['atelier_id', 'statut']);
            $table->dropIndex(['soumis_at']);
            $table->dropColumn(['statut', 'source', 'fichier_path', 'motif_refus', 'soumis_at', 'modere_at', 'modere_par']);
        });

        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];
            unset($config['max_corrections_videos_mois']);
            $plan->update(['config' => $config]);
        }
        DB::table('fonctionnalites')->where('cle', 'max_corrections_videos_mois')->delete();
    }
};
