<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paiement;
use App\Services\PaymentService;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    use LogsAdminAction;

    public function __construct(private PaymentService $paymentService) {}

    public function index(Request $request): JsonResponse
    {
        $paiements = Paiement::with(['atelier.proprietaire', 'niveau', 'validatedBy'])
            ->when($request->statut, fn($q, $s) => $q->where('statut', $s))
            ->when($request->provider, fn($q, $p) => $q->where('provider', $p))
            ->when($request->atelier_id, fn($q, $id) => $q->where('atelier_id', $id))
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($paiements);
    }

    public function valider(Request $request, Paiement $paiement): JsonResponse
    {
        $admin = $this->adminUser();

        if (! in_array($paiement->statut, ['pending', 'failed'])) {
            return response()->json(['message' => 'Ce paiement ne peut pas être validé manuellement.'], 422);
        }

        $paiement->update(['validated_by' => $admin->id]);

        $this->paymentService->activate($paiement);

        $this->audit($admin, 'paiement.valider', 'paiement', $paiement->id, [
            'montant' => $paiement->montant,
            'niveau'  => $paiement->niveau_cle,
        ], $request->ip());

        return response()->json(['message' => 'Paiement validé et abonnement activé.']);
    }

    public function rembourser(Request $request, Paiement $paiement): JsonResponse
    {
        $admin = $this->adminUser();

        if ($paiement->statut !== 'completed') {
            return response()->json(['message' => 'Seul un paiement complété peut être remboursé.'], 422);
        }

        $paiement->update([
            'statut'       => 'refunded',
            'validated_by' => $admin->id,
        ]);

        $this->audit($admin, 'paiement.rembourser', 'paiement', $paiement->id, [
            'montant' => $paiement->montant,
        ], $request->ip());

        return response()->json(['message' => 'Paiement marqué comme remboursé.']);
    }
}
