<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreClientRequest;
use App\Http\Requests\Api\UpdateClientRequest;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\EquipeMembre;
use App\Services\AtelierLimitsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
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

        $user = $request->user();

        $client = Client::create([
            'atelier_id'      => $atelier->id,
            'nom'             => $request->nom,
            'prenom'          => $request->prenom,
            'telephone'       => $request->telephone,
            'type_profil'     => $request->type_profil ?? 'standard',
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

    public function archiver(Request $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);

        $client->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Client archivé.']);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        return $user instanceof EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
