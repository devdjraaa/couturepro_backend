<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMesureRequest;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\Mesure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MesureController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request, string $clientId): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $mesures = Mesure::where('atelier_id', $atelier->id)
            ->where('client_id', $clientId)
            ->with('vetement')
            ->get();

        return response()->json($mesures);
    }

    public function store(StoreMesureRequest $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $user    = $request->user();

        $mesure = Mesure::create([
            'atelier_id'      => $atelier->id,
            'client_id'       => $request->client_id,
            'vetement_id'     => $request->vetement_id,
            'champs'          => $request->champs,
            'created_by'      => $user->id,
            'created_by_role' => $user instanceof EquipeMembre ? $user->role : 'proprietaire',
        ]);

        return response()->json($mesure, 201);
    }

    public function update(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('update', $mesure);

        $mesure->update(['champs' => $request->validate(['champs' => ['required', 'array']])['champs']]);

        return response()->json($mesure);
    }

    public function destroy(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('delete', $mesure);

        $mesure->delete();

        return response()->json(['message' => 'Mesure supprimée.']);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        return $user instanceof EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
