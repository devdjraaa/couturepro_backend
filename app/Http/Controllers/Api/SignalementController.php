<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GxtClient;
use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SignalementController extends Controller
{
    // POST /api/vitrine/signaler — signalement public (sans compte).
    //
    // ⚠️ Cette route NE SANCTIONNE PLUS toute seule. Elle appliquait avant une
    // sanction automatique au 3ᵉ signalement (atelier « gele », vêtement
    // archivé) : publique, sans limitation ni déduplication, elle permettait de
    // mettre la boutique d'un créateur hors ligne en trois requêtes HTTP.
    // On applique désormais le principe déjà retenu pour les avis : le
    // signalement alimente la file de modération, l'administrateur tranche.
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'        => ['required', 'in:profil,creation,avis'],
            'cible_id'    => ['required', 'string', 'max:64'],
            'motif'       => ['nullable', 'string', 'max:300'],
            'visitor_key' => ['nullable', 'string', 'max:64'],
        ]);

        // Un signalement doit être rattaché à quelqu'un : sans empreinte, la même
        // personne compterait autant de fois qu'elle renvoie la requête.
        $client    = auth('sanctum')->user();
        $empreinte = $client instanceof GxtClient
            ? 'client:' . $client->id
            : 'visiteur:' . ($data['visitor_key'] ?? '');

        if ($empreinte === 'visiteur:') {
            return response()->json(['message' => 'Signalement invalide.'], 422);
        }

        // Idempotent : re-signaler la même cible ne compte pas double et ne
        // révèle pas si un signalement existait déjà.
        DB::table('signalements')->insertOrIgnore([
            'id'         => (string) Str::uuid(),
            'type'       => $data['type'],
            'cible_id'   => $data['cible_id'],
            'motif'      => $data['motif'] ?? null,
            'statut'     => 'en_attente',
            'empreinte'  => substr($empreinte, 0, 64),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Signalement enregistré. Merci, notre équipe va vérifier.'], 201);
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
