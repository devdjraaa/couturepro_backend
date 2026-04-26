<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCommandeRequest;
use App\Http\Requests\Api\UpdateCommandeRequest;
use App\Models\Atelier;
use App\Models\Commande;
use App\Models\EquipeMembre;
use App\Services\AtelierLimitsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommandeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private AtelierLimitsService $limitsService) {}

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $commandes = Commande::where('atelier_id', $atelier->id)
            ->with(['client', 'vetement'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($commandes);
    }

    public function store(StoreCommandeRequest $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if (!$this->limitsService->canCreateCommande($atelier)) {
            return response()->json([
                'message' => 'Abonnement expiré. Veuillez renouveler votre abonnement.',
            ], 403);
        }

        $user = $request->user();

        $photoPath = $request->hasFile('photo_tissu')
            ? $request->file('photo_tissu')->store('tissus', 'public')
            : null;

        $acompteInitial = $request->acompte ?? 0;

        $commande = Commande::create([
            'atelier_id'            => $atelier->id,
            'client_id'             => $request->client_id,
            'vetement_id'           => $request->vetement_id,
            'created_by'            => $user->id,
            'created_by_role'       => $user instanceof EquipeMembre ? $user->role : 'proprietaire',
            'quantite'              => $request->quantite ?? 1,
            'prix'                  => $request->prix,
            'acompte'               => $acompteInitial,
            'statut'                => 'en_cours',
            'date_commande'         => now()->toDateString(),
            'date_livraison_prevue' => $request->date_livraison_prevue,
            'note_interne'          => $request->note_interne,
            'description'           => $request->description,
            'urgence'               => $request->boolean('urgence', false),
            'photo_tissu_path'      => $photoPath,
        ]);

        if ($acompteInitial > 0) {
            $commande->commandePaiements()->create([
                'atelier_id'    => $atelier->id,
                'montant'       => $acompteInitial,
                'mode_paiement' => $request->mode_paiement_acompte ?? 'especes',
                'enregistre_par' => $user->id,
            ]);
        }

        $this->limitsService->incrementCommandes($atelier);

        return response()->json($commande->load('client', 'vetement'), 201);
    }

    public function show(Request $request, Commande $commande): JsonResponse
    {
        $this->authorize('view', $commande);

        return response()->json($commande->load('client', 'vetement'));
    }

    public function update(UpdateCommandeRequest $request, Commande $commande): JsonResponse
    {
        $this->authorize('update', $commande);

        $data = $request->validated();

        if ($request->hasFile('photo_tissu')) {
            if ($commande->photo_tissu_path) {
                Storage::disk('public')->delete($commande->photo_tissu_path);
            }
            $data['photo_tissu_path'] = $request->file('photo_tissu')->store('tissus', 'public');
        }

        unset($data['photo_tissu']);

        if ($request->has('urgence')) {
            $data['urgence'] = $request->boolean('urgence');
        }

        $commande->update($data);

        return response()->json($commande->load('client', 'vetement'));
    }

    public function destroy(Request $request, Commande $commande): JsonResponse
    {
        $this->authorize('delete', $commande);

        $commande->delete();

        return response()->json(['message' => 'Commande supprimée.']);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        return $user instanceof EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
