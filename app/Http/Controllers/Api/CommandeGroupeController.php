<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandeGroupe;
use App\Models\EquipeMembre;
use App\Models\NotificationSysteme;
use App\Models\Vetement;
use App\Services\AtelierLimitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeGroupeController extends Controller
{
    use ResolvesAtelier;

    public function __construct(private AtelierLimitsService $limitsService) {}

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $groupes = CommandeGroupe::where('atelier_id', $atelier->id)
            ->with(['client', 'commandes.vetement'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json($groupes);
    }

    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if (!$this->limitsService->canCreateCommande($atelier)) {
            return response()->json([
                'message' => 'Limite de commandes du mois atteinte pour votre plan. Passez à un plan supérieur ou renouvelez votre abonnement.',
            ], 403);
        }

        $data = $request->validate([
            'client_id'     => ['required', 'uuid', 'exists:clients,id'],
            'note'          => ['nullable', 'string', 'max:1000'],
            'sous_commandes' => ['required', 'array', 'min:2'],
            // Pt 7 (20/07) : le type de vêtement est FACULTATIF — exiger sa
            // présence bloquait toute création dès qu'un article n'était pas
            // rattaché au catalogue (le front envoyait null, 422 silencieux).
            'sous_commandes.*.vetement_id'           => ['nullable', 'uuid', 'exists:vetements,id'],
            'sous_commandes.*.quantite'              => ['nullable', 'integer', 'min:1'],
            'sous_commandes.*.prix'                  => ['required', 'numeric', 'min:0'],
            'sous_commandes.*.acompte'               => ['nullable', 'numeric', 'min:0'],
            'sous_commandes.*.date_livraison_prevue' => ['nullable', 'date', 'after_or_equal:today'],
            'sous_commandes.*.description'           => ['nullable', 'string', 'max:2000'],
            'sous_commandes.*.urgence'               => ['nullable', 'boolean'],
            'sous_commandes.*.mode_paiement_acompte' => ['nullable', 'in:especes,mobile_money,virement'],
            'sous_commandes.*.motif_surplus_acompte' => ['nullable', 'string', 'max:500'], // P14-16 : motif si acompte > total
            'sous_commandes.*.photo_tissu'           => ['nullable', 'image', 'max:4096'], // P24 : photo tissu par article
        ]);

        // P14-16 : un acompte ne peut dépasser le total d'un article (prix × quantité) sans motif.
        foreach ($data['sous_commandes'] as $i => $sc) {
            $total   = (float) ($sc['prix'] ?? 0) * (int) ($sc['quantite'] ?? 1);
            $acompte = (float) ($sc['acompte'] ?? 0);
            if ($acompte > $total && empty($sc['motif_surplus_acompte'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "sous_commandes.$i.motif_surplus_acompte" =>
                        "L'acompte de l'article " . ($i + 1) . " dépasse son total. Veuillez indiquer le motif.",
                ]);
            }
        }

        // Anti-IDOR + multi-ateliers (P72-73) : client pris dans l'un des ateliers du propriétaire.
        $atelierIds = $this->ateliersAutorises($request);
        $client = Client::where('id', $data['client_id'])
            ->whereIn('atelier_id', $atelierIds)
            ->first();

        if (!$client) {
            return response()->json(['message' => 'Client introuvable pour vos ateliers.'], 422);
        }

        // Chaque vêtement doit appartenir à un de mes ateliers (ou au catalogue global).
        $vetementIds = collect($data['sous_commandes'])->pluck('vetement_id')->filter()->unique();
        $vetementsOk = Vetement::whereIn('id', $vetementIds)
            ->where(fn ($q) => $q->whereIn('atelier_id', $atelierIds)->orWhereNull('atelier_id'))
            ->count();
        if ($vetementsOk !== $vetementIds->count()) {
            return response()->json(['message' => 'Un vêtement est introuvable pour vos ateliers.'], 422);
        }

        $user = $request->user();
        $role = $user instanceof EquipeMembre ? $user->role : 'proprietaire';

        $groupe = DB::transaction(function () use ($data, $atelier, $client, $user, $role) {
            $groupe = CommandeGroupe::create([
                'atelier_id'      => $atelier->id,
                'client_id'       => $client->id,
                'created_by'      => $user->id,
                'created_by_role' => $role,
                'note'            => $data['note'] ?? null,
            ]);

            foreach ($data['sous_commandes'] as $sc) {
                $acompteInitial = $sc['acompte'] ?? 0;

                // P24 : photo du tissu propre à chaque article de la commande groupée
                $photoPath = isset($sc['photo_tissu']) && $sc['photo_tissu']
                    ? $sc['photo_tissu']->store('tissus', 'public')
                    : null;

                $commande = Commande::create([
                    'atelier_id'            => $atelier->id,
                    'client_id'             => $client->id,
                    'commande_groupe_id'    => $groupe->id,
                    'vetement_id'           => $sc['vetement_id'] ?? null,
                    'created_by'            => $user->id,
                    'created_by_role'       => $role,
                    'quantite'              => $sc['quantite'] ?? 1,
                    'prix'                  => $sc['prix'],
                    'acompte'               => $acompteInitial,
                    'statut'                => 'en_cours',
                    'date_commande'         => now()->toDateString(),
                    'date_livraison_prevue' => $sc['date_livraison_prevue'] ?? null,
                    'description'           => $sc['description'] ?? null,
                    'motif_surplus_acompte' => $sc['motif_surplus_acompte'] ?? null, // P14-16
                    'urgence'               => $sc['urgence'] ?? false,
                    'photo_tissu_path'      => $photoPath,
                ]);

                if ($acompteInitial > 0) {
                    $commande->commandePaiements()->create([
                        'atelier_id'     => $atelier->id,
                        'montant'        => $acompteInitial,
                        'mode_paiement'  => $sc['mode_paiement_acompte'] ?? 'especes',
                        'enregistre_par' => $user->id,
                    ]);
                }

                $this->limitsService->incrementCommandes($atelier);
            }

            return $groupe;
        });

        $clientNom = $client->prenom
            ? "{$client->prenom} {$client->nom}"
            : ($client->nom ?? 'Client');

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => 'Nouvelle commande groupée créée',
            'contenu'    => "Commande groupée pour {$clientNom} (" . count($data['sous_commandes']) . ' articles)',
            'type'       => 'commande_cree',
            'is_read'    => false,
        ]);

        return response()->json(
            $groupe->load(['client', 'commandes.vetement', 'commandes.items', 'commandes.echeances']),
            201
        );
    }

    public function show(Request $request, CommandeGroupe $groupe): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($groupe->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json(
            $groupe->load([
                'client',
                'commandes.vetement',
                'commandes.items',
                'commandes.echeances',
                'commandes.commandePaiements',
            ])
        );
    }
}
