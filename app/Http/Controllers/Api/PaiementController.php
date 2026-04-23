<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\Paiement;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    public function initier(Request $request): JsonResponse
    {
        $request->validate([
            'niveau_cle' => ['required', 'string', 'exists:niveaux_config,cle'],
            'provider'   => ['sometimes', 'string', 'in:fedapay'],
        ]);

        $atelier  = $this->getAtelier($request);
        $provider = $request->provider ?? config('payment.default_provider', 'fedapay');

        $paiement = $this->paymentService->initiate($atelier, $request->niveau_cle, $provider);

        return response()->json([
            'paiement_id'  => $paiement->id,
            'checkout_url' => $paiement->checkout_url,
            'expires_at'   => $paiement->expires_at,
            'montant'      => $paiement->montant,
            'devise'       => $paiement->devise,
        ], 201);
    }

    public function status(Request $request, Paiement $paiement): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($paiement->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json([
            'paiement_id'  => $paiement->id,
            'statut'       => $paiement->statut,
            'checkout_url' => $paiement->checkout_url,
            'completed_at' => $paiement->completed_at,
            'expires_at'   => $paiement->expires_at,
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
