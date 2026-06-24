<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Signalement;
use App\Models\Vetement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignalementController extends Controller
{
    // POST /api/vitrine/signaler — signalement public (sans compte).
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'     => ['required', 'in:profil,creation,avis'],
            'cible_id' => ['required', 'string', 'max:64'],
            'motif'    => ['nullable', 'string', 'max:300'],
        ]);

        Signalement::create([
            'type'     => $data['type'],
            'cible_id' => $data['cible_id'],
            'motif'    => $data['motif'] ?? null,
            'statut'   => 'en_attente',
        ]);

        $this->blocageAutomatique($data['type'], $data['cible_id']);

        return response()->json(['message' => 'Signalement enregistré. Merci.'], 201);
    }

    // Récidive : au-delà du seuil, retrait/suspension automatique (réversible par l'admin).
    private function blocageAutomatique(string $type, string $cibleId): void
    {
        $seuil = 3;
        $count = Signalement::where('type', $type)->where('cible_id', $cibleId)
            ->where('statut', 'en_attente')->count();

        if ($count < $seuil) {
            return;
        }

        match ($type) {
            'creation' => Vetement::where('id', $cibleId)->update(['is_archived' => true]),
            'profil'   => Atelier::where('id', $cibleId)->update(['statut' => 'gele']),
            'avis'     => Avis::where('id', $cibleId)->update(['statut' => 'signale']),
            default    => null,
        };
    }

    // GET /api/admin/signalements — liste pour la modération.
    public function index(Request $request): JsonResponse
    {
        $q = Signalement::query()->latest();
        if ($request->filled('statut')) {
            $q->where('statut', $request->statut);
        }

        return response()->json($q->paginate(30));
    }

    // POST /api/admin/signalements/{signalement}/traiter
    public function traiter(Request $request, Signalement $signalement): JsonResponse
    {
        $signalement->update(['statut' => 'traite']);

        return response()->json(['message' => 'Signalement traité.', 'signalement' => $signalement]);
    }
}
