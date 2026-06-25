<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\DemandeDevis;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevisController extends Controller
{
    use ResolvesAtelier;

    // POST /api/vitrine/createurs/{atelier}/devis — demande publique (sans compte).
    public function store(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->is_demo) {
            return response()->json(['message' => 'Créateur introuvable'], 404);
        }

        $data = $request->validate([
            'nom'         => ['required', 'string', 'max:80'],
            'contact'     => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:1000'],
            'budget'      => ['nullable', 'string', 'max:60'],
            'delai'       => ['nullable', 'string', 'max:60'],
            'vetement_id' => ['nullable', 'string'],
        ]);

        DemandeDevis::create([
            'atelier_id'  => $atelier->id,
            'vetement_id' => $data['vetement_id'] ?? null,
            'nom'         => $data['nom'],
            'contact'     => $data['contact'],
            'description' => $data['description'],
            'budget'      => $data['budget'] ?? null,
            'delai'       => $data['delai'] ?? null,
            'statut'      => 'nouveau',
        ]);

        return response()->json(['message' => 'Demande de devis envoyée. Le créateur vous recontactera.'], 201);
    }

    // GET /api/devis — demandes reçues par mon atelier.
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        return response()->json(
            DemandeDevis::where('atelier_id', $atelier->id)->latest()->get()
        );
    }

    // POST /api/devis/{devis}/traiter — marquer comme traité.
    public function traiter(Request $request, DemandeDevis $devis): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($devis->atelier_id === $atelier->id, 403);

        $devis->update(['statut' => 'traite']);

        return response()->json($devis);
    }
}
