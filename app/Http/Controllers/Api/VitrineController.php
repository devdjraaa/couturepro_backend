<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Commande;
use Illuminate\Http\JsonResponse;

/**
 * API PUBLIQUE de la vitrine (marketplace) — sans authentification.
 *
 * Mappe l'existant sur la forme attendue par la vitrine Next.js :
 *   Atelier  -> « créateur »
 *   Vetement -> « création »
 *
 * NB : certains champs commerciaux (spécialité, prix, catégorie, vérifié, avis,
 * collections) n'existent pas encore en base — ils sont mis à des valeurs par
 * défaut / null en attendant l'enrichissement du schéma. Aucune migration ici.
 */
class VitrineController extends Controller
{
    /** GET /api/vitrine/createurs */
    public function index(): JsonResponse
    {
        $ateliers = Atelier::query()
            ->where('is_demo', false)
            ->with('abonnement')
            ->withCount(['vetements' => fn ($q) => $q->where('is_archived', false)->where('publie_vitrine', true)])
            ->orderBy('nom')
            ->get()
            // Plan « Free » (visible_galerie = false) : profil accessible par lien direct,
            // mais non listé dans la galerie. Défaut = visible si non défini par le plan.
            ->filter(fn ($a) => ($a->abonnement?->getConfigEffective()['visible_galerie'] ?? true) !== false);

        return response()->json(
            $ateliers->map(fn ($a) => $this->creatorCard($a))->sortByDesc('sponsorise')->values()
        );
    }

    /** GET /api/vitrine/createurs/{atelier} */
    public function show(Atelier $atelier): JsonResponse
    {
        if ($atelier->is_demo) {
            return response()->json(['message' => 'Créateur introuvable'], 404);
        }

        $creations = $atelier->vetements()
            ->where('is_archived', false)
            ->where('publie_vitrine', true)
            ->latest()
            ->get()
            ->map(fn ($v) => [
                'id'        => $v->id,
                'nom'       => $v->nom,
                'image_url' => $v->image_url,
                'images'    => $v->images_urls,
                'prix'          => null,        // pas de prix en base -> « Sur devis » côté front
                'categorie'     => null,
                'type'          => 'Sur mesure',
                'collection_id' => $v->collection_id,
            ])->values();

        // Contact WhatsApp — uniquement si le créateur a activé l'opt-in.
        $whatsapp = null;
        if ($atelier->contact_public) {
            $tel = optional($atelier->proprietaire)->telephone;
            if ($tel) {
                $whatsapp = preg_replace('/\D/', '', $tel);
            }
        }

        return response()->json(array_merge($this->creatorCard($atelier), [
            'bio'       => $atelier->bio,
            'whatsapp'  => $whatsapp,
            'reseaux'   => [
                'instagram' => $atelier->instagram,
                'facebook'  => $atelier->facebook,
                'site_web'  => $atelier->site_web,
            ],
            'collections' => $atelier->collections()->orderBy('nom')->get(['id', 'nom']),
            'avis'        => $atelier->avis()->where('statut', 'valide')->latest()->get(['id', 'auteur_nom', 'note', 'texte', 'created_at']),
            'creations'   => $creations,
        ]));
    }

    /** GET /api/vitrine/suivi/{reference} — suivi public d'une commande par n°. */
    public function suivi(string $reference): JsonResponse
    {
        $c = Commande::where('reference', $reference)->with(['vetement', 'atelier'])->first();

        if (! $c) {
            return response()->json(['message' => 'Commande introuvable'], 404);
        }

        return response()->json([
            'reference'             => $c->reference,
            'modele'                => optional($c->vetement)->nom,
            'atelier'               => optional($c->atelier)->nom,
            'etape'                 => $c->etape,
            'statut'                => $c->statut,
            'date_livraison_prevue' => $c->date_livraison_prevue,
        ]);
    }

    /** Forme « carte créateur » attendue par la vitrine. */
    private function creatorCard(Atelier $a): array
    {
        return [
            'id'           => (string) $a->id,
            'nom'          => $a->nom,
            'initiales'    => $this->initiales($a->nom),
            'specialite'   => $a->specialite ?: 'Atelier de couture',
            'ville'        => $a->ville,
            'note'         => ($avgNote = $a->avis()->where('statut', 'valide')->avg('note')) ? round($avgNote, 1) : null,
            'avis'         => $a->avis()->where('statut', 'valide')->count(),
            'verifie'      => (bool) $a->verifie,
            'experience'   => null,
            'gradient'     => $this->gradient((int) $a->id),
            'logo_url'     => $a->logo_url,
            'latitude'     => $a->latitude,
            'longitude'    => $a->longitude,
            'sponsorise'   => (bool) $a->sponsorise,
            'nb_creations' => $a->vetements_count
                ?? $a->vetements()->where('is_archived', false)->where('publie_vitrine', true)->count(),
        ];
    }

    private function initiales(?string $nom): string
    {
        $parts = preg_split('/\s+/', trim((string) $nom)) ?: [];
        $ini = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            if ($p !== '') {
                $ini .= mb_strtoupper(mb_substr($p, 0, 1));
            }
        }
        return $ini !== '' ? $ini : 'GX';
    }

    private function gradient(int $seed): string
    {
        $palette = [
            'linear-gradient(135deg,#D00B0B,#7a0606)',
            'linear-gradient(135deg,#1a1a1a,#444)',
            'linear-gradient(135deg,#6F635E,#2A2A2A)',
            'linear-gradient(135deg,#D00B0B,#1a1a1a)',
            'linear-gradient(135deg,#333,#D00B0B)',
        ];
        return $palette[abs($seed) % count($palette)];
    }
}
