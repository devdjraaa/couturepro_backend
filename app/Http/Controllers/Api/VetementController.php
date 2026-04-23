<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreVetementRequest;
use App\Http\Requests\Api\UpdateVetementRequest;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\Vetement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VetementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $vetements = Vetement::where(function ($q) use ($atelier) {
                $q->where('atelier_id', $atelier->id)
                  ->orWhere('is_systeme', true);
            })
            ->actif()
            ->get();

        return response()->json($vetements);
    }

    public function store(StoreVetementRequest $request): JsonResponse
    {
        $this->authorize('create', Vetement::class);

        $atelier  = $this->getAtelier($request);
        $user     = $request->user();

        $vetement = Vetement::create([
            'atelier_id'       => $atelier->id,
            'nom'              => $request->nom,
            'libelles_mesures' => $request->libelles_mesures,
            'is_systeme'       => false,
            'is_archived'      => false,
            'created_by'       => $user->id,
            'created_by_role'  => 'proprietaire',
        ]);

        return response()->json($vetement, 201);
    }

    public function update(UpdateVetementRequest $request, Vetement $vetement): JsonResponse
    {
        $this->authorize('update', $vetement);

        $vetement->update($request->validated());

        return response()->json($vetement);
    }

    public function destroy(Request $request, Vetement $vetement): JsonResponse
    {
        $this->authorize('delete', $vetement);

        $vetement->update(['is_archived' => true]);

        return response()->json(['message' => 'Vêtement archivé.']);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        return $user instanceof EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
