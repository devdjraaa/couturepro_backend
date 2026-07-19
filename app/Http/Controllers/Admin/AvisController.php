<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * S08C-29 — Modération des avis A POSTERIORI (décision direction du 20/07).
 *
 * Les avis sont publiés automatiquement : le créateur ne les valide plus (il était
 * juge et partie et pouvait rejeter tout avis négatif). Le contrôle humain se fait
 * ensuite, ici, et uniquement par l'administration.
 *
 * La file est alimentée par les SIGNALEMENTS : depuis la correction de la faille,
 * un signalement ne dépublie plus rien, il incrémente un compteur — c'est ce
 * compteur qui fait remonter un avis dans cette file.
 */
class AvisController extends Controller
{
    /**
     * GET /admin/avis?filtre=signales|masques|tous
     * Par défaut : les avis signalés et encore visibles, les plus signalés d'abord.
     */
    public function index(Request $request): JsonResponse
    {
        $filtre = $request->query('filtre', 'signales');

        $q = Avis::with(['atelier:id,nom', 'collection:id,nom']);

        $q = match ($filtre) {
            'masques' => $q->where('statut', 'masque'),
            'tous'    => $q,
            default   => $q->where('statut', 'valide')->where('signalements_count', '>', 0),
        };

        $items = $q->orderByDesc('signalements_count')->orderByDesc('created_at')->paginate(30);

        return response()->json($items);
    }

    /** GET /admin/avis/compteurs — badge du menu (avis signalés en attente d'arbitrage). */
    public function compteurs(): JsonResponse
    {
        return response()->json([
            'signales' => Avis::where('statut', 'valide')->where('signalements_count', '>', 0)->count(),
            'masques'  => Avis::where('statut', 'masque')->count(),
            'valides'  => Avis::where('statut', 'valide')->count(),
        ]);
    }

    /** POST /admin/avis/{avis}/masquer — retire l'avis de la vitrine (arbitrage admin). */
    public function masquer(Request $request, Avis $avis): JsonResponse
    {
        $data = $request->validate(['motif' => ['nullable', 'string', 'max:300']]);

        $avis->update([
            'statut'      => 'masque',
            'motif_refus' => $data['motif'] ?? null,
        ]);

        return response()->json(['avis' => $avis->fresh()->load('atelier:id,nom')]);
    }

    /**
     * POST /admin/avis/{avis}/retablir — remet l'avis en ligne et efface le signal.
     * Indispensable : sans ce retour, un signalement abusif serait définitif.
     */
    public function retablir(Avis $avis): JsonResponse
    {
        $avis->update([
            'statut'             => 'valide',
            'signalements_count' => 0,
            'signale_at'         => null,
            'motif_refus'        => null,
        ]);

        return response()->json(['avis' => $avis->fresh()->load('atelier:id,nom')]);
    }
}
