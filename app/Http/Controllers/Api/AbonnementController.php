<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\NiveauConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbonnementController extends Controller
{
    public function plans(): JsonResponse
    {
        $plans = NiveauConfig::actif()->get([
            'cle', 'label', 'duree_jours', 'prix_xof',
            'prix_mensuel_equivalent_xof', 'description_courte', 'ordre_affichage',
        ]);

        return response()->json($plans);
    }

    public function current(Request $request): JsonResponse
    {
        $atelier    = $this->getAtelier($request);
        $abonnement = Abonnement::where('atelier_id', $atelier->id)
            ->with('niveau')
            ->latest('timestamp_debut')
            ->first();

        if (!$abonnement) {
            return response()->json(null);
        }

        // Auto-expirer si la date est passée
        if (
            in_array($abonnement->statut, ['actif', 'essai'])
            && $abonnement->timestamp_expiration?->isPast()
        ) {
            $abonnement->update(['statut' => 'expire']);
            $atelier->update(['statut' => 'expire']);
            $abonnement->refresh();
        }

        $config = $abonnement->getConfigEffective();

        return response()->json([
            'niveau_cle'           => $abonnement->niveau_cle,
            'niveau_label'         => $abonnement->niveau?->label,
            'statut'               => $abonnement->statut,
            'jours_restants'       => max(0, $abonnement->jours_restants),
            'timestamp_expiration' => $abonnement->timestamp_expiration?->toIso8601String(),
            'prix_xof'             => $abonnement->niveau?->prix_xof,
            'config'               => $config,
        ]);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        return $user instanceof EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
