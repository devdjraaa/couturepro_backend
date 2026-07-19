<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\NotificationSysteme;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvisController extends Controller
{
    use ResolvesAtelier;

    // POST /api/vitrine/createurs/{atelier}/avis — soumission publique (sans auth).
    public function store(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->is_demo) {
            return response()->json(['message' => 'Créateur introuvable'], 404);
        }

        $data = $request->validate([
            'auteur_nom' => ['required', 'string', 'max:80'],
            'note'       => ['required', 'integer', 'min:1', 'max:5'],
            'texte'      => ['nullable', 'string', 'max:600'],
            'photos'     => ['nullable', 'array', 'max:3'],   // P137 : jusqu'à 3 photos
            'photos.*'   => ['image', 'max:4096'],            // 4 Mo par photo
        ]);

        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('avis', 'public');
            }
        }

        // S08C-29 : publication AUTOMATIQUE. Le créateur ne valide plus les avis
        // déposés sur son propre profil (il était juge et partie et pouvait
        // rejeter tout avis négatif).
        Avis::create([
            'atelier_id' => $atelier->id,
            'auteur_nom' => $data['auteur_nom'],
            'note'       => $data['note'],
            'texte'      => $data['texte'] ?? null,
            'photos'     => $photos ?: null,
            'statut'     => 'valide',
        ]);

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => 'Nouvel avis reçu',
            'contenu'    => $data['auteur_nom'] . ' a laissé un avis ' . $data['note'] . '★.',
            'type'       => 'avis_recu',
            'lien'       => '/ma-vitrine',
            'is_read'    => false,
        ]);

        return response()->json(['message' => 'Merci, votre avis est publié.'], 201);
    }

    // GET /api/avis — avis de mon atelier (tous statuts).
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        return response()->json(
            Avis::where('atelier_id', $atelier->id)->latest()->get()
        );
    }

    /**
     * POST /api/avis/{avis}/moderation — RETIRÉ (S08C-29).
     *
     * La validation des avis par le créateur lui-même est supprimée : il était
     * juge et partie et pouvait rejeter tout avis négatif. Les avis sont désormais
     * publiés automatiquement ; le recours passe par le signalement, arbitré côté admin.
     *
     * On répond 410 (au lieu de supprimer la route) le temps que le front retire
     * l'écran correspondant, pour ne pas casser l'application déjà déployée.
     * → à supprimer une fois SUIVI_FRONTEND.md#S08C-29 livré.
     */
    public function moderer(): JsonResponse
    {
        return response()->json([
            'message' => 'Les avis sont désormais publiés automatiquement : la validation par le créateur a été retirée.',
        ], 410);
    }

    /**
     * POST /api/vitrine/avis/{avis}/signaler — signalement public.
     *
     * Ne dépublie PLUS l'avis (faille corrigée : un seul appel anonyme suffisait
     * à faire disparaître un avis de la vitrine, sans arbitrage ni retour possible).
     * On enregistre le signal ; l'arbitrage revient à l'administration.
     */
    public function signaler(Request $request, Avis $avis): JsonResponse
    {
        $request->validate(['motif' => ['nullable', 'string', 'max:200']]);

        if ($avis->statut === 'valide') {
            $avis->increment('signalements_count');
            $avis->update(['signale_at' => now()]);
        }

        return response()->json(['message' => 'Signalement enregistré. Merci, notre équipe va vérifier.']);
    }
}
