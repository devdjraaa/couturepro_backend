<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Avis;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvisController extends Controller
{
    use ResolvesAtelier;

    // POST /api/vitrine/createurs/{atelier}/avis — soumission publique (sans auth).
    public function store(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->is_demo) {
            return response()->json(['message' => 'Créateur introuvable'], 404);
        }

        $data = $request->validate([
            'auteur_nom' => ['required', 'string', 'max:80'],
            'note'       => ['required', 'integer', 'min:1', 'max:5'],
            'texte'      => ['nullable', 'string', 'max:600'],
        ]);

        Avis::create([
            'atelier_id' => $atelier->id,
            'auteur_nom' => $data['auteur_nom'],
            'note'       => $data['note'],
            'texte'      => $data['texte'] ?? null,
            'statut'     => 'en_attente',
        ]);

        return response()->json(['message' => 'Avis soumis, en attente de validation.'], 201);
    }

    // GET /api/avis — avis de mon atelier (tous statuts).
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        return response()->json(
            Avis::where('atelier_id', $atelier->id)->latest()->get()
        );
    }

    // POST /api/avis/{avis}/moderation — valider / rejeter (créateur).
    public function moderer(Request $request, Avis $avis): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($avis->atelier_id === $atelier->id, 403);

        $data = $request->validate(['statut' => ['required', 'in:valide,rejete']]);
        $avis->update(['statut' => $data['statut']]);

        return response()->json($avis);
    }
}
