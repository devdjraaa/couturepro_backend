<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Http\Requests\Api\StoreCommandeRequest;
use App\Http\Requests\Api\UpdateCommandeRequest;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\EquipeMembre;
use App\Models\Vetement;
use App\Models\NotificationSysteme;
use App\Services\AtelierLimitsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CommandeController extends Controller
{
    use ResolvesAtelier;
    use AuthorizesRequests;

    public function __construct(private AtelierLimitsService $limitsService) {}

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $commandes = Commande::where('atelier_id', $atelier->id)
            // P72-73 : filtre optionnel par client (fiche client web).
            ->when($request->filled('client_id'), fn ($q) => $q->where('client_id', $request->query('client_id')))
            ->with(['client', 'vetement'])
            ->orderByRaw("CASE WHEN statut = 'en_cours' THEN 0 ELSE 1 END")
            ->orderBy('date_livraison_prevue')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($commandes);
    }

    public function store(StoreCommandeRequest $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if (!$this->limitsService->canCreateCommande($atelier)) {
            return response()->json([
                'message' => 'Limite de commandes du mois atteinte pour votre plan. Passez à un plan supérieur ou renouvelez votre abonnement.',
            ], 403);
        }

        // Garde-fou anti-IDOR + support multi-ateliers (P72-73) : le client et le vêtement doivent
        // appartenir à l'un des ateliers du propriétaire (ou catalogue global pour le vêtement).
        // La commande, elle, est toujours rattachée à l'atelier actif ($atelier).
        $atelierIds = $this->ateliersAutorises($request);
        if (!Client::where('id', $request->client_id)->whereIn('atelier_id', $atelierIds)->exists()) {
            return response()->json(['message' => 'Client introuvable pour vos ateliers.'], 422);
        }
        if (!Vetement::where('id', $request->vetement_id)
                ->where(fn ($q) => $q->whereIn('atelier_id', $atelierIds)->orWhereNull('atelier_id'))
                ->exists()) {
            return response()->json(['message' => 'Vêtement introuvable pour vos ateliers.'], 422);
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
            'motif_surplus_acompte' => $request->motif_surplus_acompte,
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

        // Fidélité : ce crédit n'existait QUE sur le chemin de synchronisation hors
        // ligne — un utilisateur travaillant sur le web ne gagnait donc jamais de
        // points sur ses commandes. Idempotent : la synchro ne recréditera pas.
        app(\App\Services\PointsFideliteService::class)->crediterCreation($atelier, 'commandes', $commande->id);

        $clientNom = $commande->client?->prenom
            ? "{$commande->client->prenom} {$commande->client->nom}"
            : ($commande->client?->nom ?? 'Client');

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => 'Nouvelle commande créée',
            'contenu'    => "Commande pour {$clientNom}",
            'type'       => 'commande_cree',
            'lien'       => '/commandes/' . $commande->id,
            'is_read'    => false,
        ]);

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

        $ancienStatut = $commande->statut;
        $commande->update($data);

        // P202 + Pt 24 : commande vitrine livrée → le client est prévenu dans
        // l'application ET par e-mail. Un seul appel : deux appels distincts,
        // c'est la garantie qu'un jour l'un des deux soit oublié quelque part.
        if (($data['statut'] ?? null) === 'livre' && $ancienStatut !== 'livre') {
            app(\App\Services\NotificationsClientService::class)->pourCommande($commande, 'livree');
        }

        if (isset($data['statut']) && $data['statut'] !== $ancienStatut) {
            $atelier = $this->getAtelier($request);
            $labels  = ['livre' => 'Commande livrée', 'annule' => 'Commande annulée', 'en_cours' => 'Commande en cours'];
            NotificationSysteme::create([
                'atelier_id' => $atelier->id,
                'titre'      => $labels[$data['statut']] ?? 'Statut mis à jour',
                'contenu'    => $commande->client_nom ?? "Commande #{$commande->id}",
                'type'       => 'statut_commande',
                'lien'       => '/commandes/' . $commande->id,
                'is_read'    => false,
            ]);
        }

        return response()->json($commande->load('client', 'vetement'));
    }

    public function destroy(Request $request, Commande $commande): JsonResponse
    {
        $this->authorize('delete', $commande);

        $commande->delete();

        return response()->json(['message' => 'Commande supprimée.']);
    }

    public function archiver(Request $request, Commande $commande): JsonResponse
    {
        $this->authorize('archive', $commande);

        $note   = $request->input('note');
        $auteur = $request->user();
        $nom    = $auteur->prenom ?? $auteur->nom ?? 'Un assistant';

        $commande->update([
            'is_archived'  => true,
            'archived_at'  => now(),
            'archived_by'  => $auteur->id,
            'archive_note' => $note,
        ]);

        $atelier = $this->getAtelier($request);

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => "Commande archivée par {$nom}",
            'contenu'    => "Commande #{$commande->id} — {$commande->client_nom}" . ($note ? " : {$note}" : ''),
            'type'       => 'alerte_archive',
            'lien'       => '/commandes/' . $commande->id,
            'is_read'    => false,
        ]);

        return response()->json(['message' => 'Commande archivée.']);
    }

    public function desarchiver(Request $request, Commande $commande): JsonResponse
    {
        $this->authorize('update', $commande);

        $commande->update([
            'is_archived'  => false,
            'archived_at'  => null,
            'archived_by'  => null,
            'archive_note' => null,
        ]);

        return response()->json(['message' => 'Commande désarchivée.']);
    }

    // POST /commandes/{commande}/etape — avance l'étape de suivi (créateur).
    public function setEtape(Request $request, Commande $commande): JsonResponse
    {
        $this->authorize('update', $commande);

        $data = $request->validate(['etape' => ['required', 'in:commande,coupe,confection,essayage,livraison']]);
        $ancienneEtape = $commande->etape;
        $commande->update(['etape' => $data['etape']]);

        // P202 + Pt 24 : chaque avancée d'étape est portée au client, dans
        // l'application et par e-mail. L'étape « commande » est exclue : elle
        // correspond à la création, déjà annoncée par « commande reçue ».
        if ($data['etape'] !== $ancienneEtape && $data['etape'] !== 'commande') {
            app(\App\Services\NotificationsClientService::class)->pourCommande($commande, $data['etape']);
        }

        return response()->json($commande);
    }

}
