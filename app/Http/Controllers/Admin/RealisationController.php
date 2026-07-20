<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Realisation;
use App\Services\WatermarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Point 101 — Modération des réalisations (back-office).
 * File d'attente, approbation (avec filigrane appliqué à la publication) et refus motivé.
 */
class RealisationController extends Controller
{
    /** PHOTO-3 — fenêtre de confirmation admin pour les publications designer. */
    private const DELAI_MODERATION_HEURES = 24;

    public function __construct(private WatermarkService $watermark) {}

    /** GET /admin/realisations?statut=en_attente — file de modération (défaut : en attente). */
    public function index(Request $request): JsonResponse
    {
        $statut = $request->query('statut', Realisation::STATUT_EN_ATTENTE);
        if (! in_array($statut, Realisation::STATUTS, true)) {
            $statut = Realisation::STATUT_EN_ATTENTE;
        }

        $items = Realisation::with('atelier:id,nom,proprietaire_id,type')
            ->where('statut', $statut)
            ->orderBy('soumis_at') // les plus anciennes d'abord
            ->paginate(30);

        // PHOTO-3/7 : fenêtre de confirmation de 24 h — compte à rebours et repérage
        // des dossiers en retard, pour que la file ne s'accumule pas silencieusement.
        $items->getCollection()->transform(function (Realisation $r) {
            $limite = $r->soumis_at?->copy()->addHours(self::DELAI_MODERATION_HEURES);
            $r->setAttribute('limite_moderation', $limite);
            $r->setAttribute('en_retard', $limite?->isPast() ?? false);

            return $r;
        });

        return response()->json($items);
    }

    /** GET /admin/realisations/compteurs — nombre par statut (badges du menu admin). */
    public function compteurs(): JsonResponse
    {
        $parStatut = Realisation::selectRaw('statut, count(*) as n')
            ->groupBy('statut')
            ->pluck('n', 'statut');

        return response()->json([
            'en_attente' => (int) ($parStatut[Realisation::STATUT_EN_ATTENTE] ?? 0),
            'publiee'    => (int) ($parStatut[Realisation::STATUT_PUBLIEE] ?? 0),
            'refusee'    => (int) ($parStatut[Realisation::STATUT_REFUSEE] ?? 0),
            'brouillon'  => (int) ($parStatut[Realisation::STATUT_BROUILLON] ?? 0),
        ]);
    }

    /** POST /admin/realisations/{realisation}/approuver — publie (avec filigrane). */
    public function approuver(Request $request, Realisation $realisation): JsonResponse
    {
        if ($realisation->statut !== Realisation::STATUT_EN_ATTENTE) {
            return response()->json(['message' => 'Seules les réalisations en attente peuvent être approuvées.'], 422);
        }

        // Filigrane appliqué À LA PUBLICATION (pas à l'envoi), sur la version
        // retouchée si l'admin en a fait une — l'original reste archivé.
        $images = [];
        foreach ($realisation->images ?? [] as $img) {
            $source = $img['retouche_path'] ?? $img['path'] ?? null;
            $wm = $source ? $this->watermark->appliquer($source) : null;
            $images[] = $wm
                ? array_merge($img, ['watermark_path' => $wm['path'], 'watermark_url' => $wm['url']])
                : $img; // si le filigrane échoue, on publie l'original (jamais de blocage silencieux)
        }

        $realisation->update([
            'statut'      => Realisation::STATUT_PUBLIEE,
            'images'      => $images,
            'motif_refus' => null,
            'modere_par'  => $request->user()?->id,
            'modere_at'   => now(),
            'publie_at'   => now(),
        ]);

        return response()->json(['realisation' => $realisation->fresh()->load('atelier:id,nom')]);
    }

    /**
     * POST /admin/realisations/{realisation}/retoucher — retouche légère avant validation.
     *
     * Prévu pour les designers ne disposant pas de bons moyens techniques (recadrage,
     * ajustements simples). L'ORIGINAL est toujours conservé, même après retouche :
     * traçabilité et droits d'auteur (exigence explicite de la direction).
     */
    public function retoucher(Request $request, Realisation $realisation): JsonResponse
    {
        if ($realisation->statut !== Realisation::STATUT_EN_ATTENTE) {
            return response()->json(['message' => 'Seules les réalisations en attente peuvent être retouchées.'], 422);
        }

        $data = $request->validate([
            'path'  => ['required', 'string'],   // photo d'origine ciblée
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ]);

        $trouvee = false;
        $images  = [];
        foreach ($realisation->images ?? [] as $img) {
            if (($img['path'] ?? null) === $data['path']) {
                // Une retouche précédente est remplacée ; l'original n'est jamais touché.
                if (! empty($img['retouche_path'])) {
                    Storage::disk('public')->delete($img['retouche_path']);
                }
                $path = $request->file('photo')->store('realisations/' . $realisation->atelier_id . '/retouches', 'public');
                $img['retouche_path'] = $path;
                $img['retouche_url']  = url(Storage::url($path));
                $trouvee = true;
            }
            $images[] = $img;
        }

        if (! $trouvee) {
            return response()->json(['message' => 'Photo introuvable dans cette réalisation.'], 404);
        }

        $realisation->update(['images' => $images]);

        return response()->json(['realisation' => $realisation->fresh()]);
    }

    /**
     * GET /admin/realisations/{realisation}/fichier?path=… — sert une photo au
     * modérateur, originale ou retouchée.
     *
     * Deux raisons, pas une :
     *
     * 1. La retouche se fait sur un canvas, qui exige que l'image soit servie
     *    avec des en-têtes CORS. `/storage` n'en envoie aucun (vérifié en
     *    production) : l'image chargée en `crossOrigin` échouerait, et sans
     *    `crossOrigin` le canvas serait souillé et le rendu refusé par le
     *    navigateur. En passant par l'API — déjà autorisée pour le front — le
     *    fichier arrive en blob, donc en même origine une fois converti.
     * 2. Une réalisation en attente n'est PAS publiée. La servir depuis un
     *    chemin public la rendait lisible par quiconque devinait l'URL, avant
     *    toute modération. Ici il faut être admin.
     *
     * Le chemin demandé est confronté aux images de CETTE réalisation : sans
     * cette vérification, le paramètre permettrait de lire n'importe quel
     * fichier du disque.
     */
    public function fichier(Request $request, Realisation $realisation): mixed
    {
        $demande = (string) $request->query('path');

        $autorises = collect($realisation->images ?? [])
            ->flatMap(fn ($img) => [$img['path'] ?? null, $img['retouche_path'] ?? null])
            ->filter()
            ->all();

        if (! in_array($demande, $autorises, true)) {
            return response()->json(['message' => 'Fichier introuvable pour cette réalisation.'], 404);
        }

        if (! Storage::disk('public')->exists($demande)) {
            return response()->json(['message' => 'Fichier absent du stockage.'], 404);
        }

        return Storage::disk('public')->response($demande);
    }

    /** POST /admin/realisations/{realisation}/refuser — refuse avec motif. */
    public function refuser(Request $request, Realisation $realisation): JsonResponse
    {
        if ($realisation->statut !== Realisation::STATUT_EN_ATTENTE) {
            return response()->json(['message' => 'Seules les réalisations en attente peuvent être refusées.'], 422);
        }

        $data = $request->validate([
            'motif_refus' => ['required', 'string', 'max:500'],
        ]);

        $realisation->update([
            'statut'      => Realisation::STATUT_REFUSEE,
            'motif_refus' => $data['motif_refus'],
            'modere_par'  => $request->user()?->id,
            'modere_at'   => now(),
        ]);

        return response()->json(['realisation' => $realisation->fresh()->load('atelier:id,nom')]);
    }
}
