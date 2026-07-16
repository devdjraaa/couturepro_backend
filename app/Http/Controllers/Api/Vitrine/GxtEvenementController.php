<?php

namespace App\Http\Controllers\Api\Vitrine;

use App\Http\Controllers\Controller;
use App\Models\GxtClient;
use App\Models\GxtEvenement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// P202 / Espace Client v3 — Phase 3 : ingestion GROUPÉE des événements métier.
// Le front accumule les événements et les envoie par lot (navigator.sendBeacon) :
// 1 requête par lot, pas 1 par clic. Fonctionne connecté (jeton) ou anonyme (session_id).
// Les micro-événements (scroll, temps de page) ne passent PAS ici : GA4 uniquement.
class GxtEvenementController extends Controller
{
    private const MAX_LOT = 50;

    /** POST /vitrine/evenements — lot d'événements métier. */
    public function ingest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'              => ['required', 'string', 'max:100'],
            'appareil'                => ['nullable', 'in:mobile,desktop,tablette'],
            'evenements'              => ['required', 'array', 'min:1', 'max:'.self::MAX_LOT],
            'evenements.*.type'       => ['required', 'string', 'in:'.implode(',', GxtEvenement::TYPES)],
            'evenements.*.article_type'   => ['nullable', 'in:vetement,creation,patron'],
            'evenements.*.article_id'     => ['nullable', 'string', 'max:50'],
            'evenements.*.atelier_id'     => ['nullable', 'uuid'],
            'evenements.*.commande_id'    => ['nullable', 'uuid'],
            'evenements.*.valeur_fcfa'    => ['nullable', 'numeric', 'min:0'],
            'evenements.*.duree_secondes' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'evenements.*.metadata'       => ['nullable', 'array'],
        ]);

        // Client connecté si un jeton Sanctum « client » accompagne la requête (sinon anonyme).
        $user = auth('sanctum')->user();
        $clientId = $user instanceof GxtClient ? $user->id : null;

        $now = now();
        $lignes = collect($data['evenements'])->map(fn ($e) => [
            'id'             => (string) Str::uuid(),
            'gxt_client_id'  => $clientId,
            'session_id'     => $data['session_id'],
            'type'           => $e['type'],
            'article_type'   => $e['article_type'] ?? null,
            'article_id'     => $e['article_id'] ?? null,
            'atelier_id'     => $e['atelier_id'] ?? null,
            'commande_id'    => $e['commande_id'] ?? null,
            'valeur_fcfa'    => $e['valeur_fcfa'] ?? null,
            'duree_secondes' => $e['duree_secondes'] ?? null,
            'metadata'       => isset($e['metadata']) ? json_encode($e['metadata']) : null,
            'appareil'       => $data['appareil'] ?? null,
            'created_at'     => $now,
        ]);

        DB::table('gxt_evenements')->insert($lignes->all());

        // Compteur agrégé des recherches sans résultat (opportunités produit).
        collect($data['evenements'])
            ->where('type', 'recherche_sans_resultat')
            ->pluck('metadata.terme')
            ->filter()
            ->map(fn ($t) => mb_substr(mb_strtolower(trim($t)), 0, 100))
            ->countBy()
            ->each(fn ($n, $terme) => $this->compterRechercheVide($terme, $n));

        return response()->json(['ok' => true, 'recus' => $lignes->count()], 201);
    }

    private function compterRechercheVide(string $terme, int $n): void
    {
        $ligne = DB::table('gxt_recherches_sans_resultat')->where('terme', $terme)->first();
        if ($ligne) {
            DB::table('gxt_recherches_sans_resultat')->where('terme', $terme)
                ->update(['nombre_fois' => $ligne->nombre_fois + $n, 'updated_at' => now()]);
            $total = $ligne->nombre_fois + $n;
        } else {
            DB::table('gxt_recherches_sans_resultat')->insert([
                'id' => (string) Str::uuid(), 'terme' => $terme, 'nombre_fois' => $n,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $total = $n;
        }

        // Seuil spec : ~50 recherches infructueuses → alerte (journal + visible au dashboard admin).
        if ($total >= 50 && ($total - $n) < 50) {
            Log::notice("Recherche sans résultat fréquente : « {$terme} » ({$total} fois) — opportunité produit à signaler aux designers.");
        }
    }
}
