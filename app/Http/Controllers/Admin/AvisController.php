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
        $seuil  = (int) (\App\Models\VitrineSetting::moderationAvis()['seuil_signalements'] ?? 3);

        $q = Avis::with(['atelier:id,nom', 'vetement:id,nom']);

        $q = match ($filtre) {
            'masques' => $q->where('statut', 'masque'),
            // Décision 11 : photos jointes en attente de validation.
            'photos'  => $q->where('photos_statut', Avis::PHOTOS_EN_ATTENTE),
            'tous'    => $q,
            // File standard (décision 7) : seuil de signalements CONFIGURÉ, ou
            // revue prioritaire (motif grave / mot banni) qui court-circuite le seuil.
            default   => $q->where('statut', 'valide')->where(function ($w) use ($seuil) {
                $w->where('revue_prioritaire', true)
                  ->orWhere('signalements_count', '>=', $seuil);
            }),
        };

        // Les revues prioritaires passent TOUJOURS en tête de file.
        $items = $q->orderByDesc('revue_prioritaire')
            ->orderByDesc('signalements_count')
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json($items);
    }

    /**
     * POST /admin/avis/{avis}/photos — décision 11 : les photos jointes à un avis
     * n'apparaissent qu'après validation admin (une photo indécente visible même
     * quelques minutes cause un tort réel, contrairement à un texte retirable).
     */
    public function modererPhotos(Request $request, Avis $avis): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:valider,refuser'],
        ]);

        if (! $avis->photos) {
            return response()->json(['message' => 'Cet avis ne comporte aucune photo.'], 422);
        }

        $avis->update([
            'photos_statut' => $data['action'] === 'valider'
                ? Avis::PHOTOS_VALIDEES
                : Avis::PHOTOS_REFUSEES,
        ]);

        return response()->json(['avis' => $avis->fresh()->load('atelier:id,nom', 'vetement:id,nom')]);
    }

    /** GET /admin/avis/compteurs — badge du menu (avis signalés en attente d'arbitrage). */
    public function compteurs(): JsonResponse
    {
        $seuil = (int) (\App\Models\VitrineSetting::moderationAvis()['seuil_signalements'] ?? 3);

        return response()->json([
            'signales' => Avis::where('statut', 'valide')->where(function ($w) use ($seuil) {
                $w->where('revue_prioritaire', true)->orWhere('signalements_count', '>=', $seuil);
            })->count(),
            'photos'   => Avis::where('photos_statut', Avis::PHOTOS_EN_ATTENTE)->count(),
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
            'revue_prioritaire'  => false,
        ]);

        // Les signalements individuels sont purgés : sinon le prochain signalement
        // unique refranchirait aussitôt le seuil déjà atteint.
        \Illuminate\Support\Facades\DB::table('avis_signalements')->where('avis_id', $avis->id)->delete();

        return response()->json(['avis' => $avis->fresh()->load('atelier:id,nom')]);
    }
}
