<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Veille opportunités : collecte par `veille:opportunites`, dépôt ouvert aux
// sources extérieures (jeton partagé), lecture par l'automate et par l'admin.
class VeilleController extends Controller
{
    /**
     * POST /api/veille/ingest — dépôt d'un relevé par une source extérieure
     * (en-tête X-Veille-Token). La collecte quotidienne passe désormais par
     * `veille:opportunites` et écrit directement ; cette route reste ouverte
     * pour toute source tierce qui voudrait alimenter la veille.
     */
    public function ingest(Request $request): JsonResponse
    {
        $tokenAttendu = config('services.veille_ingest.token');
        abort_if(! $tokenAttendu || ! hash_equals($tokenAttendu, (string) $request->header('X-Veille-Token')), 401);

        $data = $request->validate([
            'semaine'              => ['required', 'date_format:Y-m-d'],
            'items'                => ['required', 'array', 'max:100'],
            'items.*.titre'        => ['required', 'string', 'max:300'],
            'items.*.lien'         => ['required', 'string', 'max:600'],
            'items.*.ia_selection' => ['nullable', 'boolean'],
            'items.*.ia_rang'      => ['nullable', 'integer', 'min:1', 'max:50'],
            'items.*.ia_raison'    => ['nullable', 'string', 'max:600'],
        ]);

        $n = 0;
        foreach ($data['items'] as $item) {
            DB::table('gxt_veille_items')->updateOrInsert(
                ['semaine' => $data['semaine'], 'lien' => mb_substr($item['lien'], 0, 600)],
                [
                    'id'           => DB::table('gxt_veille_items')
                        ->where('semaine', $data['semaine'])->where('lien', $item['lien'])->value('id') ?? (string) Str::uuid(),
                    'titre'        => mb_substr($item['titre'], 0, 300),
                    'ia_selection' => (bool) ($item['ia_selection'] ?? false),
                    'ia_rang'      => $item['ia_rang'] ?? null,
                    'ia_raison'    => $item['ia_raison'] ?? null,
                    'created_at'   => now(),
                ]
            );
            $n++;
        }

        return response()->json(['ok' => true, 'recus' => $n]);
    }

    /**
     * GET /api/veille/jour — le relevé d'une journée, pour l'envoi du digest.
     *
     * La collecte est faite par `veille:opportunites`, qui interroge une
     * trentaine de sources et fait trancher Makila. n8n n'a donc plus à la
     * refaire pour envoyer le courriel : il lit ici ce qui a déjà été établi.
     * Deux collectes concurrentes écriraient la même table avec des critères
     * différents, et le relevé dépendrait de qui a fini en dernier.
     *
     * Même jeton partagé que l'ingestion : ces deux routes servent le même
     * automate, et une veille lisible sans jeton renseignerait un concurrent
     * sur ce que nous suivons.
     */
    public function jour(Request $request): JsonResponse
    {
        $tokenAttendu = config('services.veille_ingest.token');
        abort_if(! $tokenAttendu || ! hash_equals($tokenAttendu, (string) $request->header('X-Veille-Token')), 401);

        $jour = $request->query('jour') ?: now('Africa/Porto-Novo')->toDateString();

        $items = DB::table('gxt_veille_items')->where('semaine', $jour)
            ->orderByRaw('ia_selection desc, ia_rang asc nulls last, titre asc')
            ->get(['titre', 'lien', 'ia_selection', 'ia_rang', 'ia_raison']);

        return response()->json([
            'jour'      => $jour,
            'total'     => $items->count(),
            'selection' => $items->where('ia_selection', true)->values(),
            'autres'    => $items->where('ia_selection', false)->values(),
        ]);
    }

    /**
     * GET /admin/veille — les derniers relevés, sélection de Makila en tête.
     *
     * La colonne s'appelle encore `semaine` : elle datait du rythme
     * hebdomadaire de l'automate. Elle porte désormais un JOUR. Le nom est
     * conservé pour ne pas casser la route d'ingestion, que des sources
     * extérieures peuvent appeler ; seul le pas de temps a changé.
     *
     * D'où 14 relevés au lieu de 8 : à raison d'un par jour, huit ne
     * couvraient plus qu'une semaine d'historique.
     */
    public function index(): JsonResponse
    {
        $semaines = DB::table('gxt_veille_items')
            ->select('semaine')->distinct()->orderByDesc('semaine')->limit(14)->pluck('semaine');

        $resultat = $semaines->map(function ($semaine) {
            $items = DB::table('gxt_veille_items')->where('semaine', $semaine)
                ->orderByRaw('ia_selection desc, ia_rang asc nulls last, titre asc')
                ->get(['titre', 'lien', 'ia_selection', 'ia_rang', 'ia_raison']);

            return [
                'semaine'   => $semaine,
                'selection' => $items->where('ia_selection', true)->values(),
                'autres'    => $items->where('ia_selection', false)->values(),
            ];
        });

        return response()->json($resultat);
    }
}
