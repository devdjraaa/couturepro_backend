<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\NotificationSysteme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ANN-10 — Modération des annonces A POSTERIORI (décision direction du 20/07).
 *
 * Publication libre : une annonce part en ligne sans validation préalable. La
 * modération n'intervient qu'en cas de contenu inapproprié signalé. Comme pour les
 * avis, un signalement n'enlève rien tout seul — seul un admin masque, et le
 * masquage est réversible pour qu'un signalement abusif ne soit jamais définitif.
 */
class AnnonceController extends Controller
{
    /** GET /admin/annonces?filtre=signalees|masquees|tous */
    public function index(Request $request): JsonResponse
    {
        $filtre = $request->query('filtre', 'signalees');

        $q = Annonce::with('atelier:id,nom');
        $q = match ($filtre) {
            'masquees' => $q->whereNotNull('masquee_at'),
            'tous'     => $q,
            default    => $q->whereNull('masquee_at')->where('signalements_count', '>', 0),
        };

        return response()->json(
            $q->orderByDesc('signalements_count')->orderByDesc('created_at')->paginate(30)
        );
    }

    /** GET /admin/annonces/compteurs — badge du menu. */
    public function compteurs(): JsonResponse
    {
        return response()->json([
            'signalees' => Annonce::whereNull('masquee_at')->where('signalements_count', '>', 0)->count(),
            'masquees'  => Annonce::whereNotNull('masquee_at')->count(),
            'en_ligne'  => Annonce::actives()->count(),
        ]);
    }

    /** POST /admin/annonces/{annonce}/masquer — retire l'annonce de la diffusion. */
    public function masquer(Request $request, Annonce $annonce): JsonResponse
    {
        $data = $request->validate(['motif' => ['required', 'string', 'max:300']]);

        $annonce->update(['masquee_at' => now(), 'motif_masquage' => $data['motif']]);

        // Le designer doit savoir pourquoi son annonce a disparu.
        NotificationSysteme::create([
            'atelier_id' => $annonce->atelier_id,
            'titre'      => 'Annonce retirée',
            'contenu'    => 'Votre annonce a été retirée de la diffusion : ' . $data['motif'],
            'type'       => 'annonce_masquee',
            'lien'       => '/studio',
            'is_read'    => false,
        ]);

        return response()->json(['annonce' => $annonce->fresh()->load('atelier:id,nom')]);
    }

    /** POST /admin/annonces/{annonce}/retablir — remet en diffusion et efface le signal. */
    public function retablir(Annonce $annonce): JsonResponse
    {
        $annonce->update([
            'masquee_at'         => null,
            'motif_masquage'     => null,
            'signalements_count' => 0,
            'signale_at'         => null,
        ]);

        return response()->json(['annonce' => $annonce->fresh()->load('atelier:id,nom')]);
    }
}
