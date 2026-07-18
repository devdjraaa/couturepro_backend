<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Realisation;
use App\Services\WatermarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Point 101 — Modération des réalisations (back-office).
 * File d'attente, approbation (avec filigrane appliqué à la publication) et refus motivé.
 */
class RealisationController extends Controller
{
    public function __construct(private WatermarkService $watermark) {}

    /** GET /admin/realisations?statut=en_attente — file de modération (défaut : en attente). */
    public function index(Request $request): JsonResponse
    {
        $statut = $request->query('statut', Realisation::STATUT_EN_ATTENTE);
        if (! in_array($statut, Realisation::STATUTS, true)) {
            $statut = Realisation::STATUT_EN_ATTENTE;
        }

        $items = Realisation::with('atelier:id,nom,proprietaire_id')
            ->where('statut', $statut)
            ->orderBy('soumis_at') // les plus anciennes d'abord
            ->paginate(30);

        return response()->json($items);
    }

    /** GET /admin/realisations/compteurs — nombre par statut (badges du menu admin). */
    public function compteurs(): JsonResponse
    {
        $parStatut = Realisation::selectRaw('statut, count(*) as n')
            ->groupBy('statut')
            ->pluck('n', 'statut');

        return response()->json([
            'en_attente' => (int) ($parStatut[Realisation::STATUT_EN_ATTENTE] ?? 0),
            'publiee'    => (int) ($parStatut[Realisation::STATUT_PUBLIEE] ?? 0),
            'refusee'    => (int) ($parStatut[Realisation::STATUT_REFUSEE] ?? 0),
            'brouillon'  => (int) ($parStatut[Realisation::STATUT_BROUILLON] ?? 0),
        ]);
    }

    /** POST /admin/realisations/{realisation}/approuver — publie (avec filigrane). */
    public function approuver(Request $request, Realisation $realisation): JsonResponse
    {
        if ($realisation->statut !== Realisation::STATUT_EN_ATTENTE) {
            return response()->json(['message' => 'Seules les réalisations en attente peuvent être approuvées.'], 422);
        }

        // Filigrane appliqué À LA PUBLICATION (pas à l'envoi).
        $images = [];
        foreach ($realisation->images ?? [] as $img) {
            $wm = ! empty($img['path']) ? $this->watermark->appliquer($img['path']) : null;
            $images[] = $wm
                ? array_merge($img, ['watermark_path' => $wm['path'], 'watermark_url' => $wm['url']])
                : $img; // si le filigrane échoue, on publie l'original (jamais de blocage silencieux)
        }

        $realisation->update([
            'statut'      => Realisation::STATUT_PUBLIEE,
            'images'      => $images,
            'motif_refus' => null,
            'modere_par'  => $request->user()?->id,
            'modere_at'   => now(),
            'publie_at'   => now(),
        ]);

        return response()->json(['realisation' => $realisation->fresh()->load('atelier:id,nom')]);
    }

    /** POST /admin/realisations/{realisation}/refuser — refuse avec motif. */
    public function refuser(Request $request, Realisation $realisation): JsonResponse
    {
        if ($realisation->statut !== Realisation::STATUT_EN_ATTENTE) {
            return response()->json(['message' => 'Seules les réalisations en attente peuvent être refusées.'], 422);
        }

        $data = $request->validate([
            'motif_refus' => ['required', 'string', 'max:500'],
        ]);

        $realisation->update([
            'statut'      => Realisation::STATUT_REFUSEE,
            'motif_refus' => $data['motif_refus'],
            'modere_par'  => $request->user()?->id,
            'modere_at'   => now(),
        ]);

        return response()->json(['realisation' => $realisation->fresh()->load('atelier:id,nom')]);
    }
}
