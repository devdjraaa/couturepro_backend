<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\AtelierVideo;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * PL-7 / VID-2,3,4,5 — Vidéos de présentation.
 *
 * Limite de vidéos et de corrections pilotées par le plan (plus rien en dur).
 * Aucune publication sans validation. Sur l'offre Gratuite (1 vidéo, 0 correction),
 * une nouvelle vidéo REMPLACE automatiquement l'ancienne : sans cela l'utilisateur
 * serait définitivement bloqué, ne pouvant ni ajouter ni corriger.
 */
class AtelierVideoController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    /** Limite de vidéos du plan. `null` = illimité. */
    private function limiteVideos(Atelier $atelier): ?int
    {
        $max = ($atelier->abonnement?->getConfigEffective() ?? [])['max_videos'] ?? null;

        return ($max === null || (int) $max === -1) ? null : (int) $max;
    }

    /** Corrections mensuelles autorisées par le plan (0 sur l'offre Gratuite). */
    private function maxCorrections(Atelier $atelier): int
    {
        return (int) (($atelier->abonnement?->getConfigEffective() ?? [])['max_corrections_videos_mois'] ?? 0);
    }

    /** Corrections déjà consommées sur le mois calendaire en cours. */
    private function correctionsUtilisees(string $atelierId): int
    {
        return DB::table('atelier_video_corrections')
            ->where('atelier_id', $atelierId)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    private function journaliserCorrection(string $atelierId, string $type): void
    {
        DB::table('atelier_video_corrections')->insert([
            'atelier_id' => $atelierId,
            'type'       => $type,
            'created_at' => now(),
        ]);
    }

    /** GET /atelier-videos/quota — compteurs affichés au créateur (0/1, 2/3, 5/5). */
    public function quota(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $max     = $this->limiteVideos($atelier);
        $utilise = AtelierVideo::where('atelier_id', $atelier->id)->consommentQuota()->count();
        $maxCorr = $this->maxCorrections($atelier);
        $corrUti = $this->correctionsUtilisees($atelier->id);

        return response()->json([
            'utilise'             => $utilise,
            'max'                 => $max,
            'restant'             => $max === null ? null : max(0, $max - $utilise),
            'illimite'            => $max === null,
            'corrections'         => [
                'utilisees'   => $corrUti,
                'max'         => $maxCorr,
                'restantes'   => max(0, $maxCorr - $corrUti),
                'remplacement_auto' => $maxCorr === 0 && $max === 1,
            ],
            'delai_validation_h'  => AtelierVideo::DELAI_VALIDATION_HEURES,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'videos_presentation')) {
            return $gate;
        }

        return response()->json(
            AtelierVideo::where('atelier_id', $atelier->id)->orderBy('position')->orderBy('created_at')->get()
        );
    }

    /** POST /atelier-videos — soumet une vidéo (lien YouTube ou fichier importé). */
    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'videos_presentation')) {
            return $gate;
        }

        $data = $request->validate([
            'titre'   => ['nullable', 'string', 'max:150'],
            'url'     => ['required_without:fichier', 'nullable', 'url', 'max:500'],
            'fichier' => ['required_without:url', 'nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:102400'],
        ], [
            'fichier.mimetypes' => 'Format vidéo non supporté (MP4, MOV ou WebM).',
            'fichier.max'       => 'La vidéo ne doit pas dépasser 100 Mo.',
        ]);

        $max     = $this->limiteVideos($atelier);
        $actuel  = AtelierVideo::where('atelier_id', $atelier->id)->consommentQuota();
        $utilise = (clone $actuel)->count();

        if ($max !== null && $utilise >= $max) {
            // Offre sans correction possible (Gratuit, 1 vidéo) : la nouvelle
            // REMPLACE l'ancienne, sinon le créateur serait bloqué définitivement.
            if ($this->maxCorrections($atelier) === 0 && $max === 1) {
                $ancienne = (clone $actuel)->first();
                if ($ancienne) {
                    $this->supprimerFichier($ancienne);
                    $ancienne->delete();
                }
            } else {
                $superieur = $this->planRequisPourLimite('max_videos', $max);

                return response()->json([
                    'message'           => "Limite de {$max} vidéo(s) atteinte pour votre offre.",
                    'plan_requis'       => $superieur['cle'] ?? null,
                    'plan_requis_label' => $superieur['label'] ?? null,
                    'action'            => 'upgrade',
                ], 403);
            }
        }

        $video = new AtelierVideo([
            'atelier_id' => $atelier->id,
            'titre'      => $data['titre'] ?? null,
            'position'   => (int) AtelierVideo::where('atelier_id', $atelier->id)->max('position') + 1,
            'statut'     => AtelierVideo::STATUT_EN_ATTENTE,   // aucune publication immédiate
            'soumis_at'  => now(),
        ]);

        $this->appliquerSource($video, $request, $data, $atelier->id);
        $video->save();

        return response()->json($video, 201);
    }

    /** PUT /atelier-videos/{atelier_video} — corriger une vidéo (quota mensuel). */
    public function update(Request $request, AtelierVideo $atelier_video): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($atelier_video->atelier_id === $atelier->id, 403);

        if ($resp = $this->verifierCorrection($atelier)) {
            return $resp;
        }

        $data = $request->validate([
            'titre'   => ['nullable', 'string', 'max:150'],
            'url'     => ['nullable', 'url', 'max:500'],
            'fichier' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:102400'],
        ]);

        if (array_key_exists('titre', $data)) {
            $atelier_video->titre = $data['titre'];
        }
        if (! empty($data['url']) || $request->hasFile('fichier')) {
            $this->supprimerFichier($atelier_video);
            $this->appliquerSource($atelier_video, $request, $data, $atelier->id);
        }

        // Toute correction repasse par la validation.
        $atelier_video->statut      = AtelierVideo::STATUT_EN_ATTENTE;
        $atelier_video->motif_refus = null;
        $atelier_video->soumis_at   = now();
        $atelier_video->save();

        $this->journaliserCorrection($atelier->id, 'modification');

        return response()->json($atelier_video->fresh());
    }

    public function destroy(Request $request, AtelierVideo $atelier_video): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($atelier_video->atelier_id === $atelier->id, 403);

        // Une vidéo refusée peut toujours être retirée : elle ne consomme rien.
        if ($atelier_video->statut !== AtelierVideo::STATUT_REFUSEE) {
            if ($resp = $this->verifierCorrection($atelier)) {
                return $resp;
            }
            $this->journaliserCorrection($atelier->id, 'suppression');
        }

        $this->supprimerFichier($atelier_video);
        $atelier_video->delete();

        return response()->json(['message' => 'Vidéo retirée.']);
    }

    /** Quota de corrections mensuelles (message d'upgrade si épuisé). */
    private function verifierCorrection(Atelier $atelier): ?JsonResponse
    {
        $max = $this->maxCorrections($atelier);

        if ($this->correctionsUtilisees($atelier->id) < $max) {
            return null;
        }

        $superieur = $this->planRequisPourLimite('max_corrections_videos_mois', $max);
        $message = $max === 0
            ? 'Votre offre ne permet pas de corriger une vidéo : publiez-en une nouvelle, elle remplacera l\'ancienne.'
            : "Vous avez utilisé vos {$max} correction(s) de vidéo ce mois-ci.";

        return response()->json([
            'message'           => $message,
            'code'              => 'quota_corrections',
            'plan_requis'       => $superieur['cle'] ?? null,
            'plan_requis_label' => $superieur['label'] ?? null,
            'action'            => 'upgrade',
        ], 403);
    }

    /** Renseigne la source (lien ou fichier importé) sur la vidéo. */
    private function appliquerSource(AtelierVideo $video, Request $request, array $data, string $atelierId): void
    {
        if ($request->hasFile('fichier')) {
            $path = $request->file('fichier')->store('videos/' . $atelierId, 'public');
            $video->source       = AtelierVideo::SOURCE_FICHIER;
            $video->fichier_path = $path;
            $video->url          = url(Storage::url($path));

            return;
        }

        $video->source       = AtelierVideo::SOURCE_YOUTUBE;
        $video->fichier_path = null;
        $video->url          = $data['url'];
    }

    private function supprimerFichier(AtelierVideo $video): void
    {
        if ($video->fichier_path) {
            Storage::disk('public')->delete($video->fichier_path);
        }
    }
}
