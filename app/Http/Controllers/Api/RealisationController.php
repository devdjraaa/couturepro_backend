<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Realisation;
use App\Services\QualitePhotoService;
use App\Services\WatermarkService;
use App\Traits\ChecksPlanFeature;
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
    use ResolvesAtelier, ChecksPlanFeature;

    private const MAX_IMAGES = 6;

    public function __construct(
        private QualitePhotoService $qualite,
        private WatermarkService $watermark,
    ) {}

    /** GET /realisations — toutes les réalisations de l'atelier (tous statuts). */
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $items = Realisation::where('atelier_id', $atelier->id)
            ->orderByRaw("CASE statut WHEN 'refusee' THEN 0 WHEN 'brouillon' THEN 1 WHEN 'en_attente' THEN 2 ELSE 3 END")
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'realisations' => $items,
            'quota'        => $this->infoQuota($atelier->id),
            'cycle'        => $this->quotaCycle($atelier),
        ]);
    }

    /** GET /realisations/quota — solde du cycle + anti-abus + cap du cache local. */
    public function quota(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        return response()->json(array_merge(
            $this->infoQuota($atelier->id),
            ['cycle' => $this->quotaCycle($atelier)]
        ));
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

        // PHOTO-1 : contrôle qualité automatique, immédiat. Si la photo est refusée,
        // on ne la conserve pas : le créateur reprend et renvoie autant de fois qu'il
        // veut, sans pénalité. Les codes sont traduits en icônes côté interface.
        $verdict = $this->qualite->analyser($path);
        if (! $verdict['ok']) {
            Storage::disk('public')->delete($path);

            return response()->json([
                'message'   => 'Photo non retenue par le contrôle automatique.',
                'code'      => 'qualite',
                'problemes' => $verdict['problemes'],
                'mesures'   => $verdict['mesures'],
            ], 422);
        }

        $images[] = [
            'path' => $path,
            'url'  => url(Storage::url($path)),
            // Avertissements non bloquants (ex. netteté) : affichés au créateur.
            'avertissements' => $verdict['avertissements'],
        ];
        $realisation->update(['images' => array_values($images)]);

        return response()->json([
            'realisation'    => $realisation->fresh(),
            'avertissements' => $verdict['avertissements'],
        ], 201);
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

        // PHOTO-4 : quota du cycle. Le solde est décrémenté par cet envoi ; il sera
        // réattribué si la réalisation est refusée ou supprimée avant publication.
        $atelier = Atelier::find($realisation->atelier_id);
        $cycle   = $this->quotaCycle($atelier);
        if ($cycle['bloque']) {
            $superieur = $this->planRequisPourLimite('max_realisations_cycle', $cycle['max']);

            return response()->json([
                'message'           => "Vous avez atteint votre limite de photos pour ce mois. Renouvellement le {$cycle['prochain_reset']}.",
                'code'              => 'quota_cycle',
                'cycle'             => $cycle,
                'plan_requis'       => $superieur['cle'] ?? null,
                'plan_requis_label' => $superieur['label'] ?? null,
                'action'            => 'upgrade',
            ], 403);
        }

        $commun = [
            'certifie_auteur'        => true,
            'consentement_personnes' => true,
            'motif_refus'            => null,
            'soumis_at'              => now(),
        ];

        // PHOTO-2 — ARTISAN : le contrôle automatique a déjà validé les photos à
        // l'envoi, donc publication IMMÉDIATE (le portefeuille d'un artisan est
        // volumineux : une modération humaine systématique créerait un goulot
        // d'étranglement). Le contrôle humain se fait a posteriori, par
        // échantillonnage et signalement, sans bloquer la publication.
        //
        // PHOTO-3 — DESIGNER : enjeu commercial, donc validation humaine explicite
        // dans une fenêtre de 24 h. Aucune publication sans accord d'un admin.
        if ($atelier?->type === 'artisan') {
            $realisation->update($commun + [
                'statut'    => Realisation::STATUT_PUBLIEE,
                'publie_at' => now(),
                'images'    => $this->filigraner($realisation->images ?? []),
            ]);

            return response()->json([
                'realisation' => $realisation->fresh(),
                'publication' => 'immediate',
            ]);
        }

        $realisation->update($commun + ['statut' => Realisation::STATUT_EN_ATTENTE]);

        return response()->json([
            'realisation'        => $realisation->fresh(),
            'publication'        => 'moderation',
            'delai_moderation_h' => 24,
        ]);
    }

    /** Applique le filigrane à la publication (jamais à l'envoi). */
    private function filigraner(array $images): array
    {
        return array_map(function (array $img) {
            $wm = ! empty($img['path']) ? $this->watermark->appliquer($img['path']) : null;

            return $wm
                ? array_merge($img, ['watermark_path' => $wm['path'], 'watermark_url' => $wm['url']])
                : $img;   // si le filigrane échoue, on publie l'original plutôt que de bloquer
        }, $images);
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

    /** Quota du cycle (PHOTO-4/5) : solde restant, alerte, prochain renouvellement. */
    private function quotaCycle(Atelier $atelier): array
    {
        $config = $atelier->abonnement?->getConfigEffective() ?? [];
        $max    = $config['max_realisations_cycle'] ?? null;
        $max    = ($max === null || (int) $max === -1) ? null : (int) $max;

        $utilise = Realisation::where('atelier_id', $atelier->id)->consommeesCycle()->count();
        $restant = $max === null ? null : max(0, $max - $utilise);

        return [
            'utilise'          => $utilise,
            'max'              => $max,
            'restant'          => $restant,
            'illimite'         => $max === null,
            'bloque'           => $restant !== null && $restant === 0,
            // Alerte à 80 % de consommation, avec incitation à monter en gamme.
            'alerte'           => $max !== null && $max > 0 && ($utilise / $max) >= 0.8,
            'cycle_debut'      => Realisation::debutCycle()->toDateString(),
            'prochain_reset'   => Realisation::prochainReset()->toDateString(),
        ];
    }

    /** Compteurs anti-abus + cap local + quota de cycle, pour l'UI. */
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
