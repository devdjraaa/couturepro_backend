<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Http\Requests\Api\StoreMesureRequest;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\Mesure;
use App\Models\NotificationSysteme;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MesureController extends Controller
{
    use ResolvesAtelier;
    use AuthorizesRequests;

    public function index(Request $request, string $clientId): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $mesure = Mesure::where('atelier_id', $atelier->id)
            ->where('client_id', $clientId)
            ->first();

        return response()->json($mesure);
    }

    public function store(StoreMesureRequest $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $user    = $request->user();

        $mesure = Mesure::updateOrCreate(
            ['atelier_id' => $atelier->id, 'client_id' => $request->client_id],
            [
                'champs'          => $request->champs,
                'created_by'      => $user->id,
                'created_by_role' => $user instanceof EquipeMembre ? $user->role : 'proprietaire',
            ]
        );

        return response()->json($mesure, 201);
    }

    public function update(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('update', $mesure);

        $mesure->update(['champs' => $request->validate(['champs' => ['required', 'array']])['champs']]);

        return response()->json($mesure);
    }

    public function archiver(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('archive', $mesure);

        $note   = $request->input('note');
        $auteur = $request->user();
        $nom    = $auteur->prenom ?? $auteur->nom ?? 'Un assistant';
        $client = $mesure->client;

        $mesure->update([
            'is_archived'  => true,
            'archived_at'  => now(),
            'archived_by'  => $auteur->id,
            'archive_note' => $note,
        ]);

        $atelier = $this->getAtelier($request);

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => "Mesures archivées par {$nom}",
            'contenu'    => ($client ? "{$client->prenom} {$client->nom}" : 'Client inconnu') . ($note ? " — {$note}" : ''),
            'type'       => 'alerte_archive',
            'is_read'    => false,
        ]);

        return response()->json(['message' => 'Mesures archivées.']);
    }

    public function desarchiver(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('update', $mesure);

        $mesure->update([
            'is_archived'  => false,
            'archived_at'  => null,
            'archived_by'  => null,
            'archive_note' => null,
        ]);

        return response()->json(['message' => 'Mesures désarchivées.']);
    }

    public function destroy(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('delete', $mesure);

        $mesure->delete();

        return response()->json(['message' => 'Mesure supprimée.']);
    }

}
