<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VitrineSetting;
use App\Services\MetaStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * MVP réseaux sociaux (direction, 20/07) — administration de la collecte
 * de statistiques des pages officielles. Lecture seule côté plateformes :
 * jamais de publication ni de réponse automatique.
 */
class ReseauxSociauxController extends Controller
{
    /** GET /admin/reseaux/statut — configuration (jeton masqué) + santé de la collecte. */
    public function statut(): JsonResponse
    {
        $cfg = MetaStatsService::config();

        // Le jeton ne RESSORT jamais : on n'expose que sa présence.
        $fb = $cfg['facebook'];
        $fb['token'] = $fb['token'] ? ('•••' . substr($fb['token'], -4)) : null;

        return response()->json([
            'facebook'  => $fb,
            'nb_posts'  => DB::table('reseaux_posts')->count(),
            'nb_releves'=> DB::table('reseaux_stats')->count(),
        ]);
    }

    /** PUT /admin/reseaux/facebook — poser/mettre à jour le jeton et la page. */
    public function configurerFacebook(Request $request): JsonResponse
    {
        $data = $request->validate([
            'page_id' => ['required', 'string', 'max:50'],
            'token'   => ['required', 'string', 'max:500'],
            'actif'   => ['required', 'boolean'],
        ]);

        $cfg = MetaStatsService::config();
        $cfg['facebook'] = array_merge($cfg['facebook'], $data, ['derniere_erreur' => null]);
        VitrineSetting::updateOrCreate(['cle' => 'reseaux_sociaux'], ['valeur' => $cfg]);

        // Collecte immédiate : valide le jeton tout de suite au lieu d'attendre
        // le passage planifié du lendemain — la direction saura dans la seconde
        // si la configuration est bonne.
        $res = app(MetaStatsService::class)->collecterFacebook();

        return response()->json([
            'message'  => isset($res['erreur'])
                ? 'Configuration enregistrée mais la collecte échoue : ' . $res['erreur']
                : "Configuration validée — {$res['releves']} publication(s) relevée(s).",
            'collecte' => $res,
        ], isset($res['erreur']) ? 422 : 200);
    }

    /** POST /admin/reseaux/collecter — relevé manuel (ex. juste après une publication). */
    public function collecter(MetaStatsService $service): JsonResponse
    {
        $res = $service->collecterFacebook();

        return response()->json($res, isset($res['erreur']) ? 422 : 200);
    }

    /** GET /admin/reseaux/rapport?depuis=YYYY-MM-DD&top=5 — le rapport demandé. */
    public function rapport(Request $request, MetaStatsService $service): JsonResponse
    {
        $data = $request->validate([
            'depuis' => ['nullable', 'date'],
            'top'    => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        return response()->json($service->rapport($data['depuis'] ?? null, (int) ($data['top'] ?? 5)));
    }

    /** PATCH /admin/reseaux/posts/{id} — étiqueter le sujet/thème d'un post. */
    public function etiqueter(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['sujet' => ['nullable', 'string', 'max:100']]);

        $n = DB::table('reseaux_posts')->where('id', $id)
            ->update(['sujet' => $data['sujet'], 'updated_at' => now()]);

        if (! $n) {
            return response()->json(['message' => 'Publication introuvable.'], 404);
        }

        return response()->json(['ok' => true]);
    }
}
