<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Commande;
use App\Models\CommandePaiement;
use App\Models\EquipeMembre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandePaiementController extends Controller
{
    public function index(Request $request, Commande $commande): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($commande->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json($commande->commandePaiements()->orderByDesc('created_at')->get());
    }

    public function store(Request $request, Commande $commande): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($commande->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $data = $request->validate([
            'montant'       => ['required', 'numeric', 'min:1'],
            'mode_paiement' => ['required', 'in:especes,mobile_money,virement'],
        ]);

        $user = $request->user();

        $paiement = CommandePaiement::create([
            'commande_id'   => $commande->id,
            'atelier_id'    => $atelier->id,
            'montant'       => $data['montant'],
            'mode_paiement' => $data['mode_paiement'],
            'enregistre_par' => $user->id,
        ]);

        // Met à jour le total des avances
        $commande->increment('acompte', $data['montant']);

        return response()->json($paiement, 201);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        return $user instanceof EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
