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

class ClientController extends Controller
{
    use ResolvesAtelier;
    use AuthorizesRequests;

    public function __construct(private AtelierLimitsService $limitsService) {}

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $clients = Client::where('atelier_id', $atelier->id)
            ->actif()
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
            ->where('nom', $request->nom)
            ->where('prenom', $request->prenom ?? '')
            ->exists();

        if ($doublon) {
            return response()->json([
                'message' => 'Un client avec ce nom existe déjà dans votre atelier.',
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
            'notes'           => $request->notes,
            'created_by'      => $user->id,
            'created_by_role' => $user instanceof EquipeMembre ? $user->role : 'proprietaire',
        ]);

        $this->limitsService->incrementClients($atelier);

        return response()->json($client, 201);
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

}
