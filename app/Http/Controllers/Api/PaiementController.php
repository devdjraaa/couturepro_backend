<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\Paiement;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    use ResolvesAtelier;
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

    /**
     * Vérifie un paiement par son ID FedaPay (retour utilisateur depuis la page de paiement).
     * Appelé par le frontend sur /paiement/retour?id=FEDAPAY_ID
     */
    public function verifierRetour(Request $request): JsonResponse
    {
        $request->validate(['id' => ['required', 'string']]);

        $atelier  = $this->getAtelier($request);
        $provider = config('payment.default_provider', 'fedapay');

        $paiement = Paiement::where('atelier_id', $atelier->id)
            ->where('provider', $provider)
            ->where('provider_transaction_id', $request->id)
            ->first();

        if (!$paiement) {
            return response()->json(['statut' => 'inconnu', 'message' => 'Paiement introuvable.'], 404);
        }

        // Si déjà complété (webhook reçu avant la redirect), on retourne direct
        if ($paiement->statut === 'completed') {
            return response()->json(['paiement_id' => $paiement->id, 'statut' => 'completed']);
        }

        // Sinon on vérifie le statut réel auprès de FedaPay et on active si approuvé
        try {
            $this->paymentService->handleRetour($provider, $paiement);
        } catch (\Throwable) {
            // Ne pas bloquer l'utilisateur si la vérification échoue
        }

        $paiement->refresh();

        return response()->json(['paiement_id' => $paiement->id, 'statut' => $paiement->statut]);
    }

}
