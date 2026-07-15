<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\AtelierAbonne;
use App\Models\Commande;
use App\Models\CreationLike;
use App\Models\NiveauConfig;
use App\Models\Patron;
use App\Models\Vetement;
use App\Models\VitrineEvenement;
use App\Models\VitrineSetting;
use App\Services\MeritesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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
            // Storefront public = comptes « designer » uniquement. Les artisans
            // gèrent leur atelier sans vitrine ; ils alimentent la banque de photos,
            // mais n'ont pas de profil créateur listé ici.
            ->where('type', 'designer')
            ->with('abonnement')
            ->withCount([
                'vetements' => fn ($q) => $q->where('is_archived', false)->where('publie_vitrine', true),
                'abonnes',   // P171 👥
                'commandes', // P171 🛒
            ])
            ->orderBy('nom')
            ->get()
            // Plan « Free » (visible_galerie = false) : profil accessible par lien direct,
            // mais non listé dans la galerie. Défaut = visible si non défini par le plan.
            ->filter(fn ($a) => ($a->abonnement?->getConfigEffective()['visible_galerie'] ?? true) !== false);

        return response()->json(
            $ateliers->map(fn ($a) => $this->creatorCard($a))->sortByDesc('sponsorise')->values()
        );
    }

    /** GET /api/vitrine/createurs/{atelier}?visitor_key=... */
    public function show(Request $request, Atelier $atelier, MeritesService $merites): JsonResponse
    {
        // Seuls les comptes « designer » ont un profil créateur public.
        if ($atelier->is_demo || $atelier->type !== 'designer') {
            return response()->json(['message' => 'Créateur introuvable'], 404);
        }

        // Clé visiteur anonyme (localStorage côté front) : sert à savoir ce que CE visiteur
        // a déjà liké / s'il est abonné (P159/P173). Absente = visiteur non identifié.
        $visitorKey = trim((string) $request->query('visitor_key', ''));

        $creationsModels = $atelier->vetements()
            ->where('is_archived', false)
            ->where('publie_vitrine', true)
            ->latest()
            ->get();
        $creationIds = $creationsModels->pluck('id');

        // Likes agrégés en 2 requêtes (pas de N+1) : total par création + ce que j'ai liké.
        $likeCounts = CreationLike::whereIn('vetement_id', $creationIds)
            ->selectRaw('vetement_id, count(*) as c')
            ->groupBy('vetement_id')
            ->pluck('c', 'vetement_id');
        $likedByMe = $visitorKey
            ? CreationLike::whereIn('vetement_id', $creationIds)->where('visitor_key', $visitorKey)->pluck('vetement_id')->flip()
            : collect();

        // P161 : patron payant éventuel attaché à chaque création (bouton « Télécharger »).
        $patrons = Patron::whereIn('vetement_id', $creationIds)->where('actif', true)->get()->keyBy('vetement_id');

        $creations = $creationsModels->map(fn ($v) => [
            'id'        => $v->id,
            'nom'       => $v->nom,
            'image_url' => $v->image_url,
            'images'    => $v->images_urls,
            'prix'          => null,        // pas de prix en base -> « Sur devis » côté front
            'categorie'     => null,
            'type'          => 'Sur mesure',
            'collection_id' => $v->collection_id,
            'likes'     => (int) ($likeCounts[$v->id] ?? 0),          // P159
            'liked'     => $likedByMe->has($v->id),                    // like du visiteur courant
            'patron'    => isset($patrons[$v->id]) ? [                 // P161 : contenu payant
                'id'    => $patrons[$v->id]->id,
                'titre' => $patrons[$v->id]->titre,
                'prix'  => $patrons[$v->id]->prix,
            ] : null,
        ])->values();

        // Contact WhatsApp — uniquement si le créateur a activé l'opt-in.
        $whatsapp = null;
        if ($atelier->contact_public) {
            $tel = optional($atelier->proprietaire)->telephone;
            if ($tel) {
                $whatsapp = preg_replace('/\D/', '', $tel);
            }
        }

        // Compteurs pour les mérites (P174-176).
        $vues = VitrineEvenement::where('atelier_id', $atelier->id)->where('type', 'visite')->count();
        $badges = $merites->pour([
            'likes'           => (int) $likeCounts->sum(),
            'avis'            => $atelier->avis()->where('statut', 'valide')->count(),
            'telechargements' => 0, // patrons payants = Phase 2 (non activée), P161-165
            'commandes'       => $atelier->commandes()->count(),
            'vues'            => $vues,
            'anciennete_mois' => $atelier->created_at ? (int) $atelier->created_at->diffInMonths(now()) : 0,
        ]);

        return response()->json(array_merge($this->creatorCard($atelier), [
            'bio'       => $atelier->bio,
            'whatsapp'  => $whatsapp,
            'reseaux'   => [
                'instagram' => $atelier->instagram,
                'facebook'  => $atelier->facebook,
                'site_web'  => $atelier->site_web,
                'linkedin'  => $atelier->linkedin,   // P177
                'youtube'   => $atelier->youtube,    // P177
                'tiktok'    => $atelier->tiktok,     // P177
            ],
            'inscrit_depuis' => $atelier->created_at ? $this->inscritDepuis($atelier->created_at) : null, // P172
            'abonne'         => $visitorKey ? $atelier->abonnes()->where('visitor_key', $visitorKey)->exists() : false, // P173
            'merites'        => $badges,             // P174-176
            'collections' => $atelier->collections()->orderBy('nom')->get(['id', 'nom']),
            'avis'        => $atelier->avis()->where('statut', 'valide')->latest()->get(['id', 'auteur_nom', 'note', 'texte', 'created_at']),
            'creations'   => $creations,
            // RBAC : la demande de devis est-elle incluse dans le plan du créateur ?
            'devis'       => (bool) ($atelier->abonnement?->getConfigEffective()['devis_vitrine'] ?? false),
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

    /** GET /api/vitrine/banniere — bannière publicitaire (publique). */
    public function banniere(): JsonResponse
    {
        $b = VitrineSetting::where('cle', 'banniere')->value('valeur');

        return response()->json($b ?: ['actif' => false]);
    }

    /** PUT /api/admin/vitrine/banniere — édition (admin). */
    public function setBanniere(Request $request): JsonResponse
    {
        $data = $request->validate([
            'actif' => ['required', 'boolean'],
            'texte' => ['nullable', 'string', 'max:300'],
            'lien'  => ['nullable', 'string', 'max:500'],
        ]);

        $s = VitrineSetting::updateOrCreate(['cle' => 'banniere'], ['valeur' => $data]);

        return response()->json($s->valeur);
    }

    /** GET /api/vitrine/sponsorisation — offres de mise en avant (config-driven). */
    public function sponsorisation(): JsonResponse
    {
        return response()->json(VitrineSetting::sponsorisation());
    }

    /** GET /api/vitrine/plans — offres d'abonnement actives (publique, page tarifs). */
    public function plans(): JsonResponse
    {
        return response()->json(
            NiveauConfig::actif()->get([
                'cle', 'label', 'duree_jours', 'prix_xof',
                'prix_mensuel_equivalent_xof', 'description_courte', 'config',
            ])
        );
    }

    /** PUT /api/admin/vitrine/sponsorisation — édition des offres (admin). */
    public function setSponsorisation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'actif'          => ['required', 'boolean'],
            'offres'         => ['required', 'array', 'min:1'],
            'offres.*.jours' => ['required', 'integer', 'min:1', 'max:365'],
            'offres.*.prix'  => ['required', 'integer', 'min:0'],
        ]);

        $s = VitrineSetting::updateOrCreate(['cle' => 'sponsorisation'], ['valeur' => $data]);

        return response()->json($s->valeur);
    }

    /**
     * Catalogue public de la vitrine : créations publiées de tous les designers
     * (galerie de la page d'accueil). Alimente getCreations() côté front — sinon
     * la vitrine retombe sur des modèles de démonstration.
     */
    public function creations(): JsonResponse
    {
        $creations = Vetement::query()
            ->where('is_archived', false)
            ->where('publie_vitrine', true)
            ->whereHas('atelier', fn ($q) => $q->where('is_demo', false)->where('type', 'designer'))
            ->with('atelier:id,nom')
            ->latest()
            ->limit(24)
            ->get()
            ->map(fn ($v) => [
                'id'          => (string) $v->id,
                'titre'       => $v->nom,
                'atelier_nom' => $v->atelier?->nom,
                'prix'        => null,          // pas de prix en base → « Sur devis » côté front
                'categorie'   => null,
                'type'        => 'Sur mesure',
                'gradient'    => $this->gradient((int) abs(crc32((string) $v->atelier_id))),
                'image_url'   => $v->image_url,
                'images_urls' => $v->images_urls,
            ])
            ->values();

        return response()->json($creations);
    }

    /**
     * Rendu OG côté serveur pour les robots sociaux (WhatsApp, Facebook, X, LinkedIn…)
     * qui n'exécutent pas le JavaScript : ils ne verraient sinon que les balises
     * génériques de la SPA. nginx (côté vitrine) proxifie les User-Agents de robots
     * vers cette route ; les vrais visiteurs gardent la SPA. Renvoie un HTML minimal
     * avec les balises Open Graph / Twitter du créateur + une redirection pour les
     * humains qui atterriraient ici.
     */
    public function ogCreateur(Atelier $atelier): \Illuminate\Http\Response
    {
        $vitrineUrl = rtrim(config('vitrine.url'), '/');
        $pageUrl    = $vitrineUrl . '/createurs/' . $atelier->id;

        // Créateur non public (artisan / démo) : on renvoie les balises génériques
        // et on renvoie l'humain vers l'accueil de la vitrine.
        if ($atelier->is_demo || $atelier->type !== 'designer') {
            $title = 'Gextimo — La marketplace des créateurs de mode africains';
            $desc  = 'Trouvez les meilleurs designers et tailleurs africains.';
            return $this->ogHtml($title, $desc, config('vitrine.og_image'), $vitrineUrl . '/createurs', 'website');
        }

        $nom   = $atelier->nom ?: 'Créateur';
        $ville = $atelier->ville;
        $spec  = $atelier->specialite ?: 'Atelier de couture';

        $title = $nom . ' · Gextimo';
        $desc  = $atelier->bio
            ?: trim($spec . ($ville ? ' à ' . $ville : '') . ' — découvrez ses créations sur Gextimo.');
        $desc  = mb_strimwidth(trim($desc), 0, 200, '…');
        $image = $atelier->logo_url ?: config('vitrine.og_image');

        // Données structurées pour Google (rich results).
        $note  = ($avg = $atelier->avis()->where('statut', 'valide')->avg('note')) ? round($avg, 1) : null;
        $ld = array_filter([
            '@context' => 'https://schema.org',
            '@type'    => 'LocalBusiness',
            'name'     => $nom,
            'description' => $atelier->bio ?: null,
            'image'    => $atelier->logo_url ?: null,
            'address'  => $ville ? ['@type' => 'PostalAddress', 'addressLocality' => $ville, 'addressCountry' => 'BJ'] : null,
            'url'      => $pageUrl,
            'aggregateRating' => $note ? ['@type' => 'AggregateRating', 'ratingValue' => $note, 'ratingCount' => $atelier->avis()->where('statut', 'valide')->count()] : null,
        ]);

        return $this->ogHtml($title, $desc, $image, $pageUrl, 'profile', $ld);
    }

    /** Construit la page HTML minimale de rendu OG (tout est échappé au préalable). */
    private function ogHtml(string $title, string $desc, ?string $image, string $url, string $type, ?array $ld = null): \Illuminate\Http\Response
    {
        $enc = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $title = $enc($title);
        $desc  = $enc($desc);
        $img   = $enc($image ?: config('vitrine.og_image'));
        $urlE  = $enc($url);
        $type  = $enc($type);
        $urlJs = json_encode($url, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $ldTag = $ld
            ? '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>'
            : '';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$title}</title>
<meta name="description" content="{$desc}">
<link rel="canonical" href="{$urlE}">
<meta property="og:type" content="{$type}">
<meta property="og:site_name" content="Gextimo">
<meta property="og:title" content="{$title}">
<meta property="og:description" content="{$desc}">
<meta property="og:image" content="{$img}">
<meta property="og:url" content="{$urlE}">
<meta property="og:locale" content="fr_FR">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{$title}">
<meta name="twitter:description" content="{$desc}">
<meta name="twitter:image" content="{$img}">
<meta http-equiv="refresh" content="0; url={$urlE}">
{$ldTag}
</head>
<body>
<p>Redirection vers <a href="{$urlE}">{$title}</a>…</p>
<script>location.replace({$urlJs});</script>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /** POST /api/vitrine/creations/{vetement}/like  { visitor_key } — like/unlike public (P159-160). */
    public function toggleLike(Request $request, Vetement $vetement): JsonResponse
    {
        if ($vetement->is_archived || ! $vetement->publie_vitrine) {
            return response()->json(['message' => 'Création introuvable'], 404);
        }

        $data = $request->validate(['visitor_key' => ['required', 'string', 'max:64']]);

        $existant = CreationLike::where('vetement_id', $vetement->id)
            ->where('visitor_key', $data['visitor_key'])->first();

        if ($existant) {
            $existant->delete();
            $liked = false;
        } else {
            CreationLike::create(['vetement_id' => $vetement->id, 'visitor_key' => $data['visitor_key']]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes' => CreationLike::where('vetement_id', $vetement->id)->count(),
        ]);
    }

    /** POST /api/vitrine/createurs/{atelier}/abonnement  { visitor_key } — suivre/ne plus suivre (P173). */
    public function toggleAbonnement(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->is_demo || $atelier->type !== 'designer') {
            return response()->json(['message' => 'Créateur introuvable'], 404);
        }

        $data = $request->validate(['visitor_key' => ['required', 'string', 'max:64']]);

        $existant = AtelierAbonne::where('atelier_id', $atelier->id)
            ->where('visitor_key', $data['visitor_key'])->first();

        if ($existant) {
            $existant->delete();
            $abonne = false;
        } else {
            AtelierAbonne::create(['atelier_id' => $atelier->id, 'visitor_key' => $data['visitor_key']]);
            $abonne = true;
        }

        return response()->json([
            'abonne'  => $abonne,
            'abonnes' => AtelierAbonne::where('atelier_id', $atelier->id)->count(),
        ]);
    }

    /**
     * P172 — libellé d'ancienneté « intelligent » (FR ; la vitrine est francophone).
     * < 1 mois → jours ; 1-12 mois → mois et jours ; ≥ 1 an → an(s) et mois.
     */
    private function inscritDepuis(Carbon $date): string
    {
        $now   = now();
        $jours = (int) $date->diffInDays($now);

        if ($jours < 30) {
            return "Inscrit depuis {$jours} jour" . ($jours > 1 ? 's' : '');
        }

        $mois = (int) $date->diffInMonths($now);
        if ($mois < 12) {
            $reste = (int) $date->copy()->addMonths($mois)->diffInDays($now);
            return "Inscrit depuis {$mois} mois" . ($reste > 0 ? " et {$reste} jour" . ($reste > 1 ? 's' : '') : '');
        }

        $ans       = intdiv($mois, 12);
        $moisReste = $mois % 12;
        return "Inscrit depuis {$ans} an" . ($ans > 1 ? 's' : '')
            . ($moisReste > 0 ? " et {$moisReste} mois" : '');
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
            'avis'         => $a->avis()->where('statut', 'valide')->count(), // P171 ⭐
            'abonnes'      => $a->abonnes_count   ?? $a->abonnes()->count(),   // P171 👥
            'commandes'    => $a->commandes_count ?? $a->commandes()->count(), // P171 🛒
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
