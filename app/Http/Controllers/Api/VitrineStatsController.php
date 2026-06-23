<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\VitrineEvenement;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VitrineStatsController extends Controller
{
    use ResolvesAtelier;

    // POST /api/vitrine/createurs/{atelier}/evenement — tracking public (visite | contact).
    public function evenement(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->is_demo) {
            return response()->json(['message' => 'ok']);
        }

        $data = $request->validate(['type' => ['required', 'in:visite,contact']]);

        VitrineEvenement::create([
            'atelier_id' => $atelier->id,
            'type'       => $data['type'],
        ]);

        return response()->json(['message' => 'ok'], 201);
    }

    // GET /api/vitrine-stats — agrégats du créateur connecté.
    public function mesStats(Request $request): JsonResponse
    {
        $atelier   = $this->getAtelier($request);
        $moisDebut = now()->startOfMonth();

        $base = VitrineEvenement::where('atelier_id', $atelier->id);

        return response()->json([
            'visites' => [
                'total' => (clone $base)->where('type', 'visite')->count(),
                'mois'  => (clone $base)->where('type', 'visite')->where('created_at', '>=', $moisDebut)->count(),
            ],
            'contacts' => [
                'total' => (clone $base)->where('type', 'contact')->count(),
                'mois'  => (clone $base)->where('type', 'contact')->where('created_at', '>=', $moisDebut)->count(),
            ],
        ]);
    }
}
