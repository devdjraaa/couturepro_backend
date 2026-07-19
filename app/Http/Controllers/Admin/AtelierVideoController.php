<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AtelierVideo;
use App\Models\NotificationSysteme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * VID-5 — Modération des vidéos de présentation (back-office).
 *
 * Aucune vidéo n'est publiée sans validation explicite. Objectif : éviter les
 * contenus inappropriés et garantir que la vidéo concerne bien l'activité du
 * créateur. Délai annoncé : 24 h. Un refus RESTITUE le quota (une vidéo refusée
 * ne compte plus dans la limite du plan), le créateur peut donc en resoumettre une.
 */
class AtelierVideoController extends Controller
{
    /** GET /admin/atelier-videos?statut=en_attente — file de modération. */
    public function index(Request $request): JsonResponse
    {
        $statut = $request->query('statut', AtelierVideo::STATUT_EN_ATTENTE);
        if (! in_array($statut, [AtelierVideo::STATUT_EN_ATTENTE, AtelierVideo::STATUT_PUBLIEE, AtelierVideo::STATUT_REFUSEE], true)) {
            $statut = AtelierVideo::STATUT_EN_ATTENTE;
        }

        $items = AtelierVideo::with('atelier:id,nom')
            ->where('statut', $statut)
            ->orderBy('soumis_at')          // les plus anciennes d'abord
            ->paginate(30);

        // Échéance des 24 h, pour le compte à rebours côté interface.
        $items->getCollection()->transform(function (AtelierVideo $v) {
            $v->setAttribute('limite_moderation', $v->limiteModeration());
            $v->setAttribute('en_retard', $v->limiteModeration()?->isPast() ?? false);

            return $v;
        });

        return response()->json($items);
    }

    /** GET /admin/atelier-videos/compteurs — badges du menu. */
    public function compteurs(): JsonResponse
    {
        $parStatut = AtelierVideo::selectRaw('statut, count(*) as n')->groupBy('statut')->pluck('n', 'statut');

        return response()->json([
            'en_attente' => (int) ($parStatut[AtelierVideo::STATUT_EN_ATTENTE] ?? 0),
            'publiee'    => (int) ($parStatut[AtelierVideo::STATUT_PUBLIEE] ?? 0),
            'refusee'    => (int) ($parStatut[AtelierVideo::STATUT_REFUSEE] ?? 0),
        ]);
    }

    /** POST /admin/atelier-videos/{atelier_video}/approuver */
    public function approuver(Request $request, AtelierVideo $atelier_video): JsonResponse
    {
        if ($atelier_video->statut !== AtelierVideo::STATUT_EN_ATTENTE) {
            return response()->json(['message' => 'Seules les vidéos en attente peuvent être validées.'], 422);
        }

        $atelier_video->update([
            'statut'      => AtelierVideo::STATUT_PUBLIEE,
            'motif_refus' => null,
            'modere_par'  => $request->user()?->id,
            'modere_at'   => now(),
        ]);

        NotificationSysteme::create([
            'atelier_id' => $atelier_video->atelier_id,
            'titre'      => 'Vidéo validée',
            'contenu'    => 'Votre vidéo de présentation est désormais visible sur votre profil.',
            'type'       => 'video_validee',
            'lien'       => '/studio',
            'is_read'    => false,
        ]);

        return response()->json(['video' => $atelier_video->fresh()->load('atelier:id,nom')]);
    }

    /** POST /admin/atelier-videos/{atelier_video}/refuser — le quota est restitué. */
    public function refuser(Request $request, AtelierVideo $atelier_video): JsonResponse
    {
        if ($atelier_video->statut !== AtelierVideo::STATUT_EN_ATTENTE) {
            return response()->json(['message' => 'Seules les vidéos en attente peuvent être refusées.'], 422);
        }

        $data = $request->validate(['motif_refus' => ['required', 'string', 'max:500']]);

        $atelier_video->update([
            'statut'      => AtelierVideo::STATUT_REFUSEE,
            'motif_refus' => $data['motif_refus'],
            'modere_par'  => $request->user()?->id,
            'modere_at'   => now(),
        ]);

        NotificationSysteme::create([
            'atelier_id' => $atelier_video->atelier_id,
            'titre'      => 'Vidéo non validée',
            'contenu'    => 'Votre vidéo n\'a pas été retenue : ' . $data['motif_refus'] . ' Votre quota a été restitué.',
            'type'       => 'video_refusee',
            'lien'       => '/studio',
            'is_read'    => false,
        ]);

        return response()->json(['video' => $atelier_video->fresh()->load('atelier:id,nom')]);
    }
}
