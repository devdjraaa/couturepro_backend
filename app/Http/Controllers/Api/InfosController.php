<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationSysteme;
use App\Models\VitrineSetting;
use App\Services\InfosService;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CLI-2 — « Gextimo Infos » côté professionnel.
 *
 * Onglet distinct des notifications : une notification dit ce qui est arrivé à
 * VOTRE atelier et appelle une action, une info est un message éditorial de
 * Gextimo vers la communauté. Les mélanger noierait les alertes qui comptent.
 */
class InfosController extends Controller
{
    // Résolution partagée avec les notifications : un membre d'équipe lit les
    // infos de l'atelier auquel il appartient — une info s'adresse à
    // l'établissement, pas à la personne connectée.
    use ResolvesAtelier;

    public function __construct(private InfosService $infos) {}

    // Variante tolérante : l'onglet est atteignable dès la connexion, y compris
    // avant qu'un atelier n'existe. `getAtelier()` lèverait une erreur 500 là où
    // une liste vide est la bonne réponse.
    private function atelier(Request $request): ?\App\Models\Atelier
    {
        return $this->getAtelierOuNull($request);
    }

    /** GET /api/infos — les infos qui concernent cet atelier, épinglées d'abord. */
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->atelier($request);
        if (! $atelier) {
            return response()->json(['data' => [], 'categories' => VitrineSetting::categoriesInfos()]);
        }

        return response()->json([
            'data'       => $this->infos->pourAtelier($atelier),
            // Les catégories voyagent avec la liste : l'écran n'a ainsi aucune
            // correspondance libellé/couleur codée en dur à maintenir.
            'categories' => VitrineSetting::categoriesInfos(),
        ]);
    }

    /** GET /api/infos/compteur — pastille de l'onglet. */
    public function compteur(Request $request): JsonResponse
    {
        $atelier = $this->atelier($request);

        return response()->json(['non_lues' => $atelier ? $this->infos->nonLues($atelier) : 0]);
    }

    /** POST /api/infos/{info}/lue — marque lue POUR CET ATELIER seulement. */
    public function marquerLue(Request $request, NotificationSysteme $info): JsonResponse
    {
        $atelier = $this->atelier($request);
        if (! $atelier) {
            return response()->json(['message' => 'Aucun atelier.'], 422);
        }

        // Un atelier ne doit pas pouvoir marquer lue une info qui ne lui est
        // pas destinée : sinon l'existence de messages ciblés fuit par l'API.
        if ($info->canal !== 'info' || ! $this->infos->concerne($info, $atelier)) {
            return response()->json(['message' => 'Info introuvable.'], 404);
        }

        $this->infos->marquerLue($info, $atelier);

        return response()->json(['message' => 'Info marquée comme lue.']);
    }

    /** POST /api/infos/tout-lu — vide la pastille d'un coup. */
    public function toutMarquerLu(Request $request): JsonResponse
    {
        $atelier = $this->atelier($request);
        if (! $atelier) {
            return response()->json(['message' => 'Aucun atelier.'], 422);
        }

        foreach ($this->infos->pourAtelier($atelier) as $info) {
            $this->infos->marquerLue($info, $atelier);
        }

        return response()->json(['message' => 'Toutes les infos sont marquées comme lues.']);
    }
}
