<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtelierController extends Controller
{
    use LogsAdminAction;

    public function index(Request $request): JsonResponse
    {
        $ateliers = Atelier::with(['proprietaire', 'abonnement.niveau'])
            ->withCount(['clients', 'commandes'])
            ->when($request->statut, fn($q, $s) => $q->where('statut', $s))
            ->when($request->search, fn($q, $s) =>
                $q->where('nom', 'like', "%{$s}%")
                  ->orWhereHas('proprietaire', fn($q2) => $q2->where('telephone', 'like', "%{$s}%"))
            )
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($ateliers);
    }

    public function show(Atelier $atelier): JsonResponse
    {
        $atelier->load([
            'proprietaire',
            'abonnement.niveau',
            'equipesMembres',
            'quotaMoisCourant',
            'pointsFidelite',
        ]);

        return response()->json($atelier);
    }

    public function geler(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->statut === 'gele') {
            return response()->json(['message' => 'Atelier déjà gelé.'], 422);
        }

        $admin = $this->adminUser();

        $ancienStatut = $atelier->statut;
        $atelier->update(['statut' => 'gele']);

        $this->audit($admin, 'atelier.geler', 'atelier', $atelier->id, [
            'ancien_statut' => $ancienStatut,
        ], $request->ip());

        return response()->json(['message' => 'Atelier gelé.', 'atelier' => $atelier]);
    }

    public function degeler(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->statut !== 'gele') {
            return response()->json(['message' => "L'atelier n'est pas gelé."], 422);
        }

        $admin = $this->adminUser();

        $abonnement  = $atelier->abonnement;
        $nouveauStatut = ($abonnement && $abonnement->statut === 'actif') ? 'actif' : 'expire';

        $atelier->update(['statut' => $nouveauStatut]);

        $this->audit($admin, 'atelier.degeler', 'atelier', $atelier->id, [
            'nouveau_statut' => $nouveauStatut,
        ], $request->ip());

        return response()->json(['message' => 'Atelier dégelé.', 'atelier' => $atelier]);
    }
}
