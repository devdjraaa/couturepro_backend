<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Veille opportunités : ingestion depuis n8n (jeton partagé) + consultation admin.
class VeilleController extends Controller
{
    /** POST /api/veille/ingest — appelé par n8n chaque lundi (en-tête X-Veille-Token). */
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

    /** GET /admin/veille — 8 dernières semaines, sélection IA en tête. */
    public function index(): JsonResponse
    {
        $semaines = DB::table('gxt_veille_items')
            ->select('semaine')->distinct()->orderByDesc('semaine')->limit(8)->pluck('semaine');

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
