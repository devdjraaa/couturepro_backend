<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ListeAttente;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// PL-4 : liste d'attente clients (Studio). CRUD gaté `liste_attente`.
class ListeAttenteController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'liste_attente')) {
            return $gate;
        }

        return response()->json(
            ListeAttente::where('atelier_id', $atelier->id)
                ->orderBy('position')
                ->orderBy('created_at')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'liste_attente')) {
            return $gate;
        }

        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:120'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'note'      => ['nullable', 'string', 'max:1000'],
        ]);

        $data['atelier_id'] = $atelier->id;
        $data['position']   = (int) ListeAttente::where('atelier_id', $atelier->id)->max('position') + 1;

        return response()->json(ListeAttente::create($data), 201);
    }

    public function update(Request $request, ListeAttente $liste_attente): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($liste_attente->atelier_id === $atelier->id, 403);

        $data = $request->validate([
            'nom'       => ['sometimes', 'string', 'max:120'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'note'      => ['nullable', 'string', 'max:1000'],
            'statut'    => ['sometimes', 'in:en_attente,contacte,converti,annule'],
            'position'  => ['sometimes', 'integer', 'min:0'],
        ]);

        $liste_attente->update($data);

        return response()->json($liste_attente->fresh());
    }

    public function destroy(Request $request, ListeAttente $liste_attente): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($liste_attente->atelier_id === $atelier->id, 403);

        $liste_attente->delete();

        return response()->json(['message' => 'Entrée retirée de la liste d\'attente.']);
    }
}
