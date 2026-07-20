<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Http\Requests\Api\StoreClientRequest;
use App\Http\Requests\Api\UpdateClientRequest;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\EquipeMembre;
use App\Models\NotificationSysteme;
use App\Services\AtelierLimitsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    use ResolvesAtelier;
    use AuthorizesRequests;

    public function __construct(private AtelierLimitsService $limitsService) {}

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $search = $request->query('search');

        // P69-70/76 : scope=tous → recherche dans TOUS les ateliers du propriétaire
        // (réservé au propriétaire — un membre d'équipe reste limité à son atelier).
        $user = $request->user();
        $atelierIds = [$atelier->id];
        if ($request->query('scope') === 'tous' && ! $user instanceof EquipeMembre) {
            $atelierIds = Atelier::where('proprietaire_id', $user->id)->pluck('id')->all();
        }

        // P77 : les clients « partagés » d'un autre atelier du même propriétaire
        // apparaissent aussi dans l'atelier courant (comptes multi-ateliers).
        $ownerAtelierIds = Atelier::where('proprietaire_id', $atelier->proprietaire_id)->pluck('id')->all();

        $clients = Client::actif()
            ->where(function ($q) use ($atelierIds, $ownerAtelierIds) {
                $q->whereIn('atelier_id', $atelierIds)
                  ->orWhere(fn ($s) => $s->where('partage', true)->whereIn('atelier_id', $ownerAtelierIds));
            })
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('nom', 'like', "%{$search}%")
                   ->orWhere('prenom', 'like', "%{$search}%")
                   ->orWhere('telephone', 'like', "%{$search}%");
            }))
            ->withCount('commandes')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($clients);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if (!$this->limitsService->canCreateClient($atelier)) {
            return response()->json([
                'message' => 'Quota mensuel de clients atteint. Passez à un plan supérieur.',
            ], 403);
        }

        $doublon = Client::where('atelier_id', $atelier->id)
            ->where(function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('nom', $request->nom)
                        ->where('prenom', $request->prenom ?? '');
                });
                if ($request->filled('telephone')) {
                    $q->orWhere('telephone', $request->telephone);
                }
            })
            ->exists();

        if ($doublon) {
            return response()->json([
                'message' => 'Un client avec ce nom ou ce numéro existe déjà dans votre atelier.',
                'code'    => 'doublon',
            ], 422);
        }

        $user = $request->user();

        $client = Client::create([
            'atelier_id'      => $atelier->id,
            'nom'             => $request->nom,
            'prenom'          => $request->prenom,
            'telephone'       => $request->telephone,
            'type_profil'     => $request->type_profil ?? 'mixte',
            'avatar_index'    => $request->avatar_index,
            'is_vip'          => $request->boolean('is_vip', false),
            'partage'         => $request->boolean('partage', false), // P77
            'notes'           => $request->notes,
            'created_by'      => $user->id,
            'created_by_role' => $user instanceof EquipeMembre ? $user->role : 'proprietaire',
        ]);

        $this->limitsService->incrementClients($atelier);

        // Fidélité : ce crédit n'existait QUE sur le chemin de synchronisation hors
        // ligne — un utilisateur travaillant sur le web ne gagnait donc jamais de
        // points sur ses clients. Idempotent : la synchro ne recréditera pas.
        app(\App\Services\PointsFideliteService::class)->crediterCreation($atelier, 'clients', $client->id);

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => 'Nouveau client ajouté',
            'contenu'    => trim("{$client->prenom} {$client->nom}"),
            'type'       => 'client_cree',
            'lien'       => '/clients/' . $client->id,
            'is_read'    => false,
        ]);

        return response()->json($client, 201);
    }

    public function importBatch(Request $request): JsonResponse
    {
        $request->validate([
            'contacts'              => ['required', 'array', 'max:200'],
            'contacts.*.nom'        => ['required', 'string', 'max:100'],
            'contacts.*.prenom'     => ['nullable', 'string', 'max:100'],
            'contacts.*.telephone'  => ['required', 'string', 'max:30'],
        ]);

        $atelier = $this->getAtelier($request);
        $user    = $request->user();

        $existing = Client::where('atelier_id', $atelier->id)
            ->pluck('telephone')
            ->map(fn ($t) => preg_replace('/\D/', '', $t))
            ->filter()
            ->flip()
            ->all();

        $imported = 0;
        $skipped  = 0;

        foreach ($request->contacts as $c) {
            $normalized = preg_replace('/\D/', '', $c['telephone']);
            if (isset($existing[$normalized])) {
                $skipped++;
                continue;
            }

            Client::create([
                'atelier_id'      => $atelier->id,
                'nom'             => $c['nom'],
                'prenom'          => $c['prenom'] ?? null,
                'telephone'       => $c['telephone'],
                'type_profil'     => 'mixte',
                'created_by'      => $user->id,
                'created_by_role' => $user instanceof EquipeMembre ? $user->role : 'proprietaire',
            ]);

            $existing[$normalized] = true;
            $imported++;
        }

        return response()->json([
            'imported' => $imported,
            'skipped'  => $skipped,
            'total'    => count($request->contacts),
        ]);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        return response()->json($client->load('mesures', 'commandes'));
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return response()->json($client);
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return response()->json(['message' => 'Client supprimé.']);
    }

    public function toggleVip(Request $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $client->update(['is_vip' => ! $client->is_vip]);

        return response()->json(['is_vip' => $client->is_vip]);
    }

    public function archiver(Request $request, Client $client): JsonResponse
    {
        $this->authorize('archive', $client);

        $note = $request->input('note');

        $client->update([
            'is_archived'  => true,
            'archived_at'  => now(),
            'archived_by'  => $request->user()->id,
            'archive_note' => $note,
        ]);

        $atelier = $this->getAtelier($request);
        $auteur  = $request->user();
        $nom     = $auteur->prenom ?? $auteur->nom ?? 'Un assistant';

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => "Client archivé par {$nom}",
            'contenu'    => "{$client->prenom} {$client->nom}" . ($note ? " — {$note}" : ''),
            'type'       => 'alerte_archive',
            'lien'       => '/clients/' . $client->id,
            'is_read'    => false,
        ]);

        return response()->json(['message' => 'Client archivé.']);
    }

    public function desarchiver(Request $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $client->update([
            'is_archived'  => false,
            'archived_at'  => null,
            'archived_by'  => null,
            'archive_note' => null,
        ]);

        return response()->json(['message' => 'Client désarchivé.']);
    }

    // #72-77 — Recherche cross-ateliers (tous les ateliers du propriétaire)
    public function searchGlobal(Request $request): JsonResponse
    {
        $search = $request->query('q', '');
        if (strlen($search) < 2) {
            return response()->json(['message' => 'Recherche trop courte (min 2 caractères).'], 422);
        }

        $user = $request->user();
        // Propriétaire uniquement : récupère tous ses ateliers
        $atelierIds = Atelier::where('proprietaire_id', $user->id)->pluck('id');

        $clients = Client::whereIn('atelier_id', $atelierIds)
            ->where('is_archived', false)
            ->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            })
            ->with('atelier:id,nom')
            ->withCount('commandes')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'nom'            => $c->nom,
                'prenom'         => $c->prenom,
                'telephone'      => $c->telephone,
                'avatar_index'   => $c->avatar_index,
                'is_vip'         => $c->is_vip,
                'commandes_count'=> $c->commandes_count,
                'atelier_id'     => $c->atelier_id,
                'atelier_nom'    => $c->atelier?->nom,
            ]);

        return response()->json($clients);
    }
}
