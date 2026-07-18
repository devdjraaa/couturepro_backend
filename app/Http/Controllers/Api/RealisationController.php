<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Realisation;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Point 101 — « Mes Réalisations » côté professionnel (artisan/designer).
 * Brouillon → soumission en modération → publiée/refusée. Certification d'auteur
 * et consentement des personnes obligatoires à la soumission ; anti-abus 10/sem.
 */
class RealisationController extends Controller
{
    use ResolvesAtelier;

    private const MAX_IMAGES = 6;

    /** GET /realisations — toutes les réalisations de l'atelier (tous statuts). */
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $items = Realisation::where('atelier_id', $atelier->id)
            ->orderByRaw("CASE statut WHEN 'refusee' THEN 0 WHEN 'brouillon' THEN 1 WHEN 'en_attente' THEN 2 ELSE 3 END")
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['realisations' => $items, 'quota' => $this->infoQuota($atelier->id)]);
    }

    /** GET /realisations/quota — état anti-abus + cap du cache local. */
    public function quota(Request $request): JsonResponse
    {
        return response()->json($this->infoQuota($this->getAtelier($request)->id));
    }

    /** POST /realisations — crée un brouillon. */
    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        // Cap du cache local (brouillons + en attente uniquement).
        $enCache = Realisation::where('atelier_id', $atelier->id)
            ->whereIn('statut', [Realisation::STATUT_BROUILLON, Realisation::STATUT_EN_ATTENTE])
            ->count();
        if ($enCache >= Realisation::CAP_CACHE_LOCAL) {
            return response()->json([
                'message' => 'Limite de ' . Realisation::CAP_CACHE_LOCAL . ' réalisations en brouillon/attente atteinte. Publiez ou supprimez des brouillons.',
            ], 422);
        }

        $data = $request->validate([
            'titre'                  => ['required', 'string', 'max:120'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'certifie_auteur'        => ['nullable', 'boolean'],
            'consentement_personnes' => ['nullable', 'boolean'],
        ]);

        $r = Realisation::create([
            'atelier_id'             => $atelier->id,
            'titre'                  => $data['titre'],
            'description'            => $data['description'] ?? null,
            'images'                 => [],
            'statut'                 => Realisation::STATUT_BROUILLON,
            'certifie_auteur'        => (bool) ($data['certifie_auteur'] ?? false),
            'consentement_personnes' => (bool) ($data['consentement_personnes'] ?? false),
        ]);

        return response()->json(['realisation' => $r], 201);
    }

    /** PUT /realisations/{realisation} — édite un brouillon (ou une réalisation refusée). */
    public function update(Request $request, Realisation $realisation): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $realisation)) {
            return $resp;
        }
        if (! $realisation->estEditable()) {
            return response()->json(['message' => 'Cette réalisation n\'est plus modifiable.'], 422);
        }

        $data = $request->validate([
            'titre'                  => ['sometimes', 'required', 'string', 'max:120'],
            'description'            => ['nullable', 'string', 'max:2000'],
            'certifie_auteur'        => ['nullable', 'boolean'],
            'consentement_personnes' => ['nullable', 'boolean'],
        ]);

        // Une réalisation refusée repasse en brouillon dès qu'on l'édite.
        if ($realisation->statut === Realisation::STATUT_REFUSEE) {
            $data['statut'] = Realisation::STATUT_BROUILLON;
            $data['motif_refus'] = null;
        }

        $realisation->update($data);

        return response()->json(['realisation' => $realisation->fresh()]);
    }

    /** POST /realisations/{realisation}/photo — ajoute une photo au brouillon. */
    public function ajouterPhoto(Request $request, Realisation $realisation): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $realisation)) {
            return $resp;
        }
        if (! $realisation->estEditable()) {
            return response()->json(['message' => 'Cette réalisation n\'est plus modifiable.'], 422);
        }

        $images = $realisation->images ?? [];
        if (count($images) >= self::MAX_IMAGES) {
            return response()->json(['message' => 'Maximum ' . self::MAX_IMAGES . ' photos par réalisation.'], 422);
        }

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ]);

        $path = $request->file('photo')->store('realisations/' . $realisation->atelier_id, 'public');
        $images[] = ['path' => $path, 'url' => url(Storage::url($path))];
        $realisation->update(['images' => array_values($images)]);

        return response()->json(['realisation' => $realisation->fresh()], 201);
    }

    /** DELETE /realisations/{realisation}/photo — retire une photo (par son path). */
    public function retirerPhoto(Request $request, Realisation $realisation): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $realisation)) {
            return $resp;
        }
        if (! $realisation->estEditable()) {
            return response()->json(['message' => 'Cette réalisation n\'est plus modifiable.'], 422);
        }

        $data = $request->validate(['path' => ['required', 'string']]);
        $restantes = [];
        foreach ($realisation->images ?? [] as $img) {
            if (($img['path'] ?? null) === $data['path']) {
                Storage::disk('public')->delete($img['path']);
                if (! empty($img['watermark_path'])) {
                    Storage::disk('public')->delete($img['watermark_path']);
                }
                continue;
            }
            $restantes[] = $img;
        }
        $realisation->update(['images' => array_values($restantes)]);

        return response()->json(['realisation' => $realisation->fresh()]);
    }

    /** POST /realisations/{realisation}/soumettre — envoie en modération. */
    public function soumettre(Request $request, Realisation $realisation): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $realisation)) {
            return $resp;
        }
        if (! in_array($realisation->statut, [Realisation::STATUT_BROUILLON, Realisation::STATUT_REFUSEE], true)) {
            return response()->json(['message' => 'Cette réalisation ne peut pas être soumise.'], 422);
        }

        // Certification d'auteur + consentement obligatoires (bloquant).
        $request->validate([
            'certifie_auteur'        => ['accepted'],
            'consentement_personnes' => ['accepted'],
        ], [
            'certifie_auteur.accepted'        => 'Vous devez certifier être l\'auteur de ces photos.',
            'consentement_personnes.accepted' => 'Vous devez confirmer le consentement des personnes visibles.',
        ]);

        if (empty($realisation->images)) {
            return response()->json(['message' => 'Ajoutez au moins une photo avant de soumettre.'], 422);
        }

        // Anti-abus : 10 soumissions / 7 jours glissants / atelier.
        $recentes = Realisation::where('atelier_id', $realisation->atelier_id)
            ->where('soumis_at', '>=', now()->subDays(7))
            ->count();
        if ($recentes >= Realisation::MAX_ENVOIS_SEMAINE) {
            return response()->json([
                'message' => 'Limite de ' . Realisation::MAX_ENVOIS_SEMAINE . ' envois par semaine atteinte. Réessayez plus tard.',
            ], 429);
        }

        $realisation->update([
            'statut'                 => Realisation::STATUT_EN_ATTENTE,
            'certifie_auteur'        => true,
            'consentement_personnes' => true,
            'motif_refus'            => null,
            'soumis_at'              => now(),
        ]);

        return response()->json(['realisation' => $realisation->fresh()]);
    }

    /** DELETE /realisations/{realisation} — supprime (avec ses fichiers). */
    public function destroy(Request $request, Realisation $realisation): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $realisation)) {
            return $resp;
        }

        foreach ($realisation->images ?? [] as $img) {
            if (! empty($img['path'])) {
                Storage::disk('public')->delete($img['path']);
            }
            if (! empty($img['watermark_path'])) {
                Storage::disk('public')->delete($img['watermark_path']);
            }
        }
        $realisation->delete();

        return response()->json(['message' => 'Réalisation supprimée.']);
    }

    /** Garde-fou d'appartenance (l'atelier courant possède bien cette réalisation). */
    private function refuserSiPasProprietaire(Request $request, Realisation $realisation): ?JsonResponse
    {
        // Le propriétaire possède tous ses ateliers : on autorise l'atelier maître
        // comme n'importe lequel de ses sous-ateliers autorisés.
        if (! in_array($realisation->atelier_id, $this->ateliersAutorises($request), true)) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return null;
    }

    /** Compteurs anti-abus + cap local pour l'UI. */
    private function infoQuota(string $atelierId): array
    {
        $envoiesSemaine = Realisation::where('atelier_id', $atelierId)
            ->where('soumis_at', '>=', now()->subDays(7))
            ->count();
        $enCache = Realisation::where('atelier_id', $atelierId)
            ->whereIn('statut', [Realisation::STATUT_BROUILLON, Realisation::STATUT_EN_ATTENTE])
            ->count();

        return [
            'envois_semaine'      => $envoiesSemaine,
            'max_envois_semaine'  => Realisation::MAX_ENVOIS_SEMAINE,
            'envois_restants'     => max(0, Realisation::MAX_ENVOIS_SEMAINE - $envoiesSemaine),
            'cache_local_utilise' => $enCache,
            'cache_local_max'     => Realisation::CAP_CACHE_LOCAL,
        ];
    }
}
