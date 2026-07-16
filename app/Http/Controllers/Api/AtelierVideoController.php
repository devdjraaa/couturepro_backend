<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AtelierVideo;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PL-7 : vidéos de présentation (Studio). Gaté `videos_presentation`, plafond 50.
class AtelierVideoController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    private const MAX_VIDEOS = 50;

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

        if (AtelierVideo::where('atelier_id', $atelier->id)->count() >= self::MAX_VIDEOS) {
            abort(403, 'Limite de ' . self::MAX_VIDEOS . ' vidéos de présentation atteinte.');
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
