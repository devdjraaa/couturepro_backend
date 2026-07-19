<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AtelierVideo;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PL-7 / VID-2 : vidéos de présentation. La limite est PILOTÉE PAR LE PLAN
// (Gratuit 1 · Atelier 3 · Studio 5) — auparavant 50 en dur, identique pour tous.
class AtelierVideoController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    /** Limite du plan courant. `null` = illimité (clé absente ou -1). */
    private function limiteVideos(\App\Models\Atelier $atelier): ?int
    {
        $config = $atelier->abonnement?->getConfigEffective() ?? [];
        $max    = $config['max_videos'] ?? null;

        return ($max === null || (int) $max === -1) ? null : (int) $max;
    }

    /** GET /atelier-videos/quota — compteur affiché côté créateur (0/1, 2/3, 5/5). */
    public function quota(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $max     = $this->limiteVideos($atelier);
        $utilise = AtelierVideo::where('atelier_id', $atelier->id)->count();

        return response()->json([
            'utilise'  => $utilise,
            'max'      => $max,
            'restant'  => $max === null ? null : max(0, $max - $utilise),
            'illimite' => $max === null,
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

    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'videos_presentation')) {
            return $gate;
        }

        $max = $this->limiteVideos($atelier);
        if ($max !== null && AtelierVideo::where('atelier_id', $atelier->id)->count() >= $max) {
            $superieur = $this->planRequisPourLimite('max_videos', $max);

            return response()->json([
                'message'           => "Limite de {$max} vidéo(s) atteinte pour votre offre.",
                'plan_requis'       => $superieur['cle'] ?? null,
                'plan_requis_label' => $superieur['label'] ?? null,
                'action'            => 'upgrade',
            ], 403);
        }

        $data = $request->validate([
            'titre' => ['nullable', 'string', 'max:150'],
            'url'   => ['required', 'url', 'max:500'],
        ]);

        $data['atelier_id'] = $atelier->id;
        $data['position']   = (int) AtelierVideo::where('atelier_id', $atelier->id)->max('position') + 1;

        return response()->json(AtelierVideo::create($data), 201);
    }

    public function destroy(Request $request, AtelierVideo $atelier_video): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($atelier_video->atelier_id === $atelier->id, 403);

        $atelier_video->delete();

        return response()->json(['message' => 'Vidéo retirée.']);
    }
}
