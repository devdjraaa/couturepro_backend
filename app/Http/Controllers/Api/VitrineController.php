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
use App\Models\GxtClient;
use App\Models\VitrineEvenement;
use App\Models\VitrineSetting;
use App\Services\EvenementCelebrationService;
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
                // ABO-8 : un désabonnement CONSERVE la ligne (traçabilité) — sans ce
                // filtre, la galerie continuerait de compter les partis, alors que la
                // fiche du créateur, elle, ne compte que les actifs. Deux chiffres
                // différents pour le même créateur selon la page.
                'abonnes' => fn ($q) => $q->where('actif', true), // P171 👥
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
            'bio'            => $atelier->bio,
            'banniere_url'   => $atelier->banniere_url,   // P134
            'banniere_type'  => $atelier->banniere_type,  // image | video
            // VIT-3 : sans ce champ, le cadrage choisi par le créateur ne
            // parviendrait jamais au profil public — l'image y resterait
            // centrée d'office, et le réglage n'aurait aucun effet visible.
            'banniere_cadrage' => $atelier->banniere_cadrage,
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
            // P173 / ABO-1 : l'abonnement est rattaché au COMPTE client, plus à la clé
            // visiteur anonyme. Le lire encore par `visitor_key` renverrait toujours
            // `false` : le bouton afficherait « Suivre » à quelqu'un déjà abonné.
            'abonne'         => ($client = auth('sanctum')->user()) instanceof GxtClient
                ? $atelier->abonnes()->where('gxt_client_id', $client->id)->where('actif', true)->exists()
                : false,
            'merites'        => $badges,             // P174-176
            'badge_pro'      => (bool) ($atelier->abonnement?->getConfigEffective()['badge_designer_pro'] ?? false), // PL-8
            'videos'         => (($atelier->abonnement?->getConfigEffective()['videos_presentation'] ?? false)) // PL-7
                // VID-5 : seules les vidéos VALIDÉES sont visibles publiquement
                // (sans ce filtre, une vidéo en attente ou refusée s'afficherait).
                ? \App\Models\AtelierVideo::where('atelier_id', $atelier->id)->publiees()->orderBy('position')->get(['titre', 'url', 'source'])
                : [],
            'collections' => $atelier->collections()->orderBy('nom')->get(['id', 'nom', 'annonce_message', 'annonce_at']), // PL-6
            // Avis v2 (20/07) : chaque avis porte le MODÈLE visé (`vetement_id`,
            // nul = avis historique niveau créateur). Les photos ne sortent que
            // VALIDÉES (décision 11) — l'ancien code exposait les photos brutes.
            'avis'        => $atelier->avis()->where('statut', 'valide')->with('vetement:id,nom')->latest()
                ->get()
                ->map(fn ($av) => [
                    'id'            => $av->id,
                    'vetement_id'   => $av->vetement_id,
                    'vetement_nom'  => $av->vetement?->nom,
                    'auteur_nom'    => $av->auteur_nom,
                    'note'          => $av->note,
                    'texte'         => $av->texte,
                    'photos_urls'   => $av->photosPubliques(),
                    'achat_verifie' => $av->achat_verifie,   // décision 9 : badge futur
                    'created_at'    => $av->created_at,
                ]),
            'creations'   => $creations,
            // Point 101 : réalisations publiées (photos filigranées, modérées).
            'realisations' => \App\Models\Realisation::where('atelier_id', $atelier->id)
                ->publiees()
                ->latest('publie_at')
                ->get()
                ->map(fn ($r) => [
                    'id'          => $r->id,
                    'titre'       => $r->titre,
                    'description' => $r->description,
                    'images'      => collect($r->images ?? [])
                        ->map(fn ($im) => $im['watermark_url'] ?? ($im['url'] ?? null))
                        ->filter()->values(),
                    'publie_at'   => $r->publie_at,
                ]),
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

    /**
     * GET /api/vitrine/annonces — annonces en cours de diffusion (publique).
     * Alimente la bande défilante en haut de l'application : les annonces boostées
     * passent en premier. Aucune route ne listait les annonces de TOUS les ateliers.
     */
    public function annonces(): JsonResponse
    {
        $annonces = \App\Models\Annonce::actives()
            ->ordreDiffusion()
            ->with('atelier:id,nom')
            ->limit(30)
            ->get()
            ->map(fn ($a) => [
                'id'        => $a->id,
                'titre'     => $a->titre,
                'message'   => $a->message,
                'image_url' => $a->image_url,
                'boostee'   => $a->boost_en_cours,
                'createur'  => ['id' => $a->atelier?->id, 'nom' => $a->atelier?->nom],
            ]);

        return response()->json([
            'annonces'            => $annonces,
            'diffusions_par_jour' => VitrineSetting::boostAnnonce()['diffusions_par_jour'] ?? 3,
        ]);
    }

    /**
     * POST /api/vitrine/annonces/{annonce}/signaler — signalement public (ANN-10).
     * Ne retire RIEN : incrémente un compteur qui fait remonter l'annonce dans la
     * file de modération. L'arbitrage revient à l'administration.
     */
    public function signalerAnnonce(\App\Models\Annonce $annonce): JsonResponse
    {
        if (! $annonce->masquee_at) {
            $annonce->increment('signalements_count');
            $annonce->update(['signale_at' => now()]);
        }

        return response()->json(['message' => 'Signalement enregistré. Merci, notre équipe va vérifier.']);
    }

    /** GET /api/vitrine/banniere — bannière publicitaire (publique). */
    public function banniere(): JsonResponse
    {
        $b = VitrineSetting::where('cle', 'banniere')->value('valeur');

        return response()->json($b ?: ['actif' => false]);
    }

    /**
     * GET /api/vitrine/splash-theme — thème saisonnier ACTIF aujourd'hui (brief 16/07 point 6).
     * Habillage local (Ramadan, fêtes…) : overlay de 2-3 s à l'ouverture, configuré par l'admin
     * (périodes datées, visuels vérifiés béninois). Vide = pas d'overlay.
     */
    public function splashTheme(): JsonResponse
    {
        $themes = VitrineSetting::where('cle', 'splash_themes')->value('valeur') ?: [];
        $today = now()->toDateString();

        $actif = collect($themes)->first(fn ($t) => ($t['actif'] ?? false)
            && ($t['date_debut'] ?? '9999') <= $today
            && ($t['date_fin'] ?? '0000') >= $today);

        return response()->json($actif ?: ['actif' => false]);
    }

    /**
     * GET /api/admin/vitrine/splash-themes — TOUTES les périodes (admin).
     *
     * La route publique `GET /vitrine/splash-theme` ne renvoie que la période
     * ACTIVE du jour : elle sert l'application, pas l'administration. Sans cette
     * lecture, l'écran d'admin ne pouvait pas afficher les périodes existantes —
     * on ne pouvait qu'écraser la liste à l'aveugle.
     */
    public function getSplashThemes(): JsonResponse
    {
        return response()->json([
            'themes' => VitrineSetting::where('cle', 'splash_themes')->value('valeur') ?: [],
        ]);
    }

    /** PUT /api/admin/vitrine/splash-themes — liste des périodes thématiques (admin). */
    public function setSplashThemes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'themes'                => ['required', 'array', 'max:20'],
            'themes.*.nom'          => ['required', 'string', 'max:60'],
            'themes.*.actif'        => ['required', 'boolean'],
            'themes.*.image_url'    => ['nullable', 'string', 'max:500'],
            'themes.*.texte'        => ['nullable', 'string', 'max:150'],
            'themes.*.date_debut'   => ['required', 'date_format:Y-m-d'],
            'themes.*.date_fin'     => ['required', 'date_format:Y-m-d', 'after_or_equal:themes.*.date_debut'],
        ]);

        $s = VitrineSetting::updateOrCreate(['cle' => 'splash_themes'], ['valeur' => $data['themes']]);

        return response()->json($s->valeur);
    }

    /**
     * GET /api/vitrine/evenements — événements de célébration du jour (point 57).
     * Auth optionnelle : si un client est connecté, son anniversaire est injecté.
     * Triés par priorité décroissante ; le front affiche le plus prioritaire non
     * encore vu (fréquence gérée côté client).
     */
    public function evenements(Request $request, EvenementCelebrationService $moteur): JsonResponse
    {
        $user = auth('sanctum')->user();
        $client = $user instanceof GxtClient ? $user : null;
        $contexte = $request->query('contexte') === 'app' ? 'app' : 'vitrine';

        return response()->json([
            'evenements' => $moteur->duJour($client, $contexte),
        ]);
    }

    /**
     * GET /api/moyens-paiement — moyens de paiement actifs (facturation).
     * Source unique consommée par le front : plus aucune liste en dur.
     */
    public function moyensPaiement(): JsonResponse
    {
        $actifs = collect(VitrineSetting::moyensPaiement())
            ->filter(fn ($m) => $m['actif'] ?? true)
            ->values();

        return response()->json([
            'moyens' => $actifs,
            'defaut' => $actifs->firstWhere('defaut', true)['cle'] ?? $actifs->first()['cle'] ?? null,
        ]);
    }

    /** PUT /api/admin/vitrine/moyens-paiement — édition de la liste (admin). */
    public function setMoyensPaiement(Request $request): JsonResponse
    {
        $data = $request->validate([
            'moyens'         => ['required', 'array', 'min:1', 'max:20'],
            'moyens.*.cle'   => ['required', 'string', 'max:30'],
            'moyens.*.label' => ['required', 'string', 'max:60'],
            'moyens.*.actif' => ['required', 'boolean'],
            'moyens.*.defaut'=> ['nullable', 'boolean'],
        ]);

        $s = VitrineSetting::updateOrCreate(['cle' => 'moyens_paiement'], ['valeur' => $data['moyens']]);

        return response()->json(['moyens' => $s->valeur]);
    }

    /**
     * POST /api/vitrine/vasat/acces — accès à l'espace VASAT (produit masqué).
     *
     * Directive direction (20/07) : VASAT est intégré au site mais protégé par
     * mot de passe, pour développer en arrière-plan sans exposition publique.
     * Premier mot de passe saisi = mot de passe de référence (TOFU) ; ensuite
     * exigé à chaque accès. Limité en débit contre la force brute.
     */
    public function accesVasat(Request $request): JsonResponse
    {
        $data = $request->validate(['mdp' => ['required', 'string', 'min:6', 'max:100']]);

        $cfg = VitrineSetting::vasat();
        if (! ($cfg['actif'] ?? true)) {
            return response()->json(['message' => 'Espace indisponible.'], 404);
        }

        // ⚠️ Le mot de passe ne s'auto-définit PLUS à la première saisie.
        // Ce principe (« premier arrivé ») convient au tracker, qui n'a aucun
        // lien public. VASAT, lui, est référencé au pied du site : n'importe
        // quel visiteur aurait pu s'en emparer en tapant six caractères.
        // Il se pose uniquement depuis l'administration.
        if (empty($cfg['mdp_hash'])) {
            return response()->json([
                'message' => 'Espace indisponible.',
                'code'    => 'non_configure',
            ], 404);
        }

        if (! \Illuminate\Support\Facades\Hash::check($data['mdp'], $cfg['mdp_hash'])) {
            return response()->json(['message' => 'Mot de passe incorrect.'], 403);
        }

        return response()->json(['ok' => true]);
    }

    /** GET /api/admin/vitrine/moderation-avis — réglages de modération des avis. */
    public function getModerationAvis(): JsonResponse
    {
        return response()->json(['reglages' => VitrineSetting::moderationAvis()]);
    }

    /**
     * PUT /api/admin/vitrine/moderation-avis — seuils, motifs graves et mots
     * bannis, éditables sans redéploiement (décisions 7, 8 et 12 du 20/07 —
     * la liste de mots « s'enrichit progressivement avec l'usage réel »).
     */
    public function setModerationAvis(Request $request): JsonResponse
    {
        $data = $request->validate([
            'max_avis_par_jour'  => ['required', 'integer', 'min:1', 'max:100'],
            'seuil_signalements' => ['required', 'integer', 'min:1', 'max:100'],
            'motifs_graves'      => ['required', 'array', 'max:10'],
            'motifs_graves.*'    => ['string', 'max:30'],
            'mots_bannis'        => ['present', 'array', 'max:500'],
            'mots_bannis.*'      => ['string', 'max:60'],
        ]);

        $s = VitrineSetting::updateOrCreate(['cle' => 'moderation_avis'], ['valeur' => $data]);

        return response()->json(['reglages' => $s->valeur]);
    }

    /**
     * PUT /api/admin/vitrine/identite-legale — identité légale de la société.
     *
     * Alimente les 11 pages juridiques (RCCM et IFU dans les mentions légales,
     * numéro de délibération APDP dans les deux pages de données personnelles,
     * dates d'entrée en vigueur partout). Chaque champ accepte la chaîne vide :
     * tant qu'un numéro n'est pas attribué, la ligne correspondante disparaît de
     * la page plutôt que d'afficher un gabarit « à compléter ».
     */
    public function setIdentiteLegale(Request $request): JsonResponse
    {
        $data = $request->validate([
            'rccm'                => ['present', 'nullable', 'string', 'max:60'],
            'ifu'                 => ['present', 'nullable', 'string', 'max:60'],
            'apdp_deliberation'   => ['present', 'nullable', 'string', 'max:80'],
            'date_entree_vigueur' => ['present', 'nullable', 'string', 'max:40'],
            'date_maj'            => ['present', 'nullable', 'string', 'max:40'],
        ]);

        // On normalise en chaîne : le front distingue « vide » de « absent »
        // pour décider s'il affiche la ligne — un null casserait ce test.
        $data = array_map(fn ($v) => trim((string) $v), $data);

        $s = VitrineSetting::updateOrCreate(['cle' => 'identite_legale'], ['valeur' => $data]);

        return response()->json(['identite' => $s->valeur]);
    }

    /**
     * PUT /api/admin/vitrine/journal-maj — CLI-1, « Quoi de neuf ».
     *
     * Éditable en admin parce que les publications sont automatiques au push :
     * exiger un déploiement pour décrire une version reviendrait à ne jamais la
     * décrire.
     */
    public function setJournalMaj(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entrees'             => ['present', 'array', 'max:100'],
            'entrees.*.version'   => ['required', 'string', 'max:20'],
            'entrees.*.date'      => ['required', 'date_format:Y-m-d'],
            'entrees.*.titre'     => ['required', 'string', 'max:120'],
            'entrees.*.type'      => ['required', 'in:nouveaute,amelioration,correction'],
            'entrees.*.lignes'    => ['present', 'array', 'max:30'],
            'entrees.*.lignes.*'  => ['string', 'max:300'],
        ]);

        VitrineSetting::updateOrCreate(['cle' => 'journal_maj'], ['valeur' => $data['entrees']]);

        return response()->json(['entrees' => VitrineSetting::journalMaj()]);
    }

    /**
     * PUT /api/admin/vitrine/compte-a-rebours — CLI-3.
     *
     * Volontairement générique : la direction voudra rejouer ce compte à rebours
     * pour d'autres annonces que le lancement du 22 août. Date, textes, couleur
     * et seuil d'apparition sont donc tous éditables — une date en dur aurait
     * imposé un redéploiement à chaque réutilisation.
     */
    public function setCompteARebours(Request $request): JsonResponse
    {
        $data = $request->validate([
            'actif'         => ['required', 'boolean'],
            'date_cible'    => ['required', 'date_format:Y-m-d H:i'],
            'jours_avant'   => ['required', 'integer', 'min:1', 'max:365'],
            'titre'         => ['required', 'string', 'max:80'],
            'texte_bande'   => ['required', 'string', 'max:160'],
            'texte_jour_j'  => ['required', 'string', 'max:160'],
            'couleur'       => ['required', 'string', 'max:9'],
            'lien'          => ['present', 'nullable', 'string', 'max:300'],
            'chrono_jour_j' => ['required', 'boolean'],
        ]);

        $s = VitrineSetting::updateOrCreate(['cle' => 'compte_a_rebours'], ['valeur' => $data]);

        return response()->json(['compte_a_rebours' => $s->valeur]);
    }

    /** GET /api/admin/vitrine/paliers-fidelite — paliers du programme (admin). */
    public function getPaliersFidelite(): JsonResponse
    {
        return response()->json(['paliers' => VitrineSetting::paliersFidelite()]);
    }

    /**
     * PUT /api/admin/vitrine/paliers-fidelite — recalibrage des paliers (admin).
     * Permet d'ajuster le programme sans redéploiement (les seuils étaient en dur).
     */
    public function setPaliersFidelite(Request $request): JsonResponse
    {
        $data = $request->validate([
            'paliers'          => ['required', 'array', 'min:1', 'max:10'],
            'paliers.*.cle'    => ['required', 'string', 'max:30'],
            'paliers.*.nom'    => ['required', 'string', 'max:40'],
            'paliers.*.seuil'  => ['required', 'integer', 'min:0'],
        ]);

        // Ordre croissant garanti : la logique de progression parcourt la liste en
        // séquence, un ordre incohérent fausserait le palier courant.
        $paliers = collect($data['paliers'])->sortBy('seuil')->values()->all();

        $s = VitrineSetting::updateOrCreate(['cle' => 'paliers_fidelite'], ['valeur' => $paliers]);

        return response()->json(['paliers' => $s->valeur]);
    }

    /** GET /api/admin/vitrine/evenements — catalogue brut (admin). */
    public function getEvenements(EvenementCelebrationService $moteur): JsonResponse
    {
        return response()->json(['catalogue' => VitrineSetting::evenementsCelebration()]);
    }

    /** PUT /api/admin/vitrine/evenements — édition du catalogue d'événements (admin). */
    public function setEvenements(Request $request): JsonResponse
    {
        $data = $request->validate([
            'catalogue'                        => ['required', 'array', 'max:100'],
            'catalogue.*.code'                 => ['required', 'string', 'max:60'],
            'catalogue.*.type'                 => ['required', 'in:fixe,lunaire,gextimo,utilisateur,marketing'],
            'catalogue.*.date_fixe'            => ['nullable', 'date_format:m-d'],
            'catalogue.*.dates'                => ['nullable', 'array', 'max:20'],
            'catalogue.*.dates.*'              => ['date_format:Y-m-d'],
            'catalogue.*.date_debut'           => ['nullable', 'date_format:Y-m-d'],
            'catalogue.*.date_fin'             => ['nullable', 'date_format:Y-m-d'],
            'catalogue.*.titre'                => ['required', 'array'],
            'catalogue.*.titre.fr'             => ['required', 'string', 'max:120'],
            'catalogue.*.titre.en'             => ['required', 'string', 'max:120'],
            'catalogue.*.message'              => ['nullable', 'array'],
            'catalogue.*.message.fr'           => ['nullable', 'string', 'max:300'],
            'catalogue.*.message.en'           => ['nullable', 'string', 'max:300'],
            'catalogue.*.animation'            => ['required', 'in:confettis,coeurs,neige,etoiles,aucune'],
            'catalogue.*.couleur'              => ['nullable', 'string', 'max:9'],
            'catalogue.*.image_url'            => ['nullable', 'string', 'max:500'],
            'catalogue.*.priorite'             => ['nullable', 'integer', 'min:0', 'max:999'],
            'catalogue.*.cible'                => ['required', 'in:tous,clients,pros'],
            'catalogue.*.mode_affichage'       => ['required', 'in:splash,toast'],
            'catalogue.*.frequence_affichage'  => ['required', 'in:quotidien,unique'],
            'catalogue.*.actif'                => ['required', 'boolean'],
        ]);

        $s = VitrineSetting::updateOrCreate(['cle' => 'evenements_celebration'], ['valeur' => $data['catalogue']]);

        return response()->json(['catalogue' => $s->valeur]);
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
        // Reco v1 (brief 16/07 point 4) : si un client vitrine est connecté, ses designers
        // « favoris » (déduits de SES événements : vues, likes, paniers, commandes) remontent
        // en tête de galerie. Anonyme = ordre chronologique inchangé.
        $ateliersFavoris = [];
        $user = auth('sanctum')->user();
        if ($user instanceof \App\Models\GxtClient) {
            $ateliersFavoris = \Illuminate\Support\Facades\DB::table('gxt_evenements')
                ->where('gxt_client_id', $user->id)
                ->whereNotNull('atelier_id')
                ->selectRaw('atelier_id, count(*) as n')
                ->groupBy('atelier_id')
                ->orderByDesc('n')
                ->limit(5)
                ->pluck('atelier_id')
                ->all();
        }

        $creations = Vetement::query()
            ->where('is_archived', false)
            ->where('publie_vitrine', true)
            ->whereHas('atelier', fn ($q) => $q->where('is_demo', false)->where('type', 'designer'))
            ->with('atelier:id,nom')
            ->latest()
            ->limit(24)
            ->get()
            ->sortBy(fn ($v) => [array_search($v->atelier_id, $ateliersFavoris) === false ? 99 : array_search($v->atelier_id, $ateliersFavoris), 0])
            ->values()
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

        // ABO-1 : un compte est désormais OBLIGATOIRE. Auparavant l'abonnement était
        // anonyme (clé visiteur en stockage navigateur) : il ne garantissait ni suivi,
        // ni notification, ni fiabilité du compteur.
        $user = auth('sanctum')->user();
        if (! $user instanceof GxtClient) {
            return response()->json([
                'message' => 'Créez un compte ou connectez-vous pour suivre ce créateur.',
                'code'    => 'auth_requise',   // le front ouvre le module d'inscription
            ], 401);
        }

        // ABO-6 : un créateur ne peut pas s'abonner à lui-même (compteur gonflé).
        if ($user->email && $user->email === optional($atelier->proprietaire)->email) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous abonner à votre propre profil.',
                'code'    => 'auto_abonnement',
            ], 422);
        }

        $data = $request->validate([
            'notifications_optin' => ['nullable', 'boolean'],   // ABO-5 : consentement distinct
        ]);

        $ligne = AtelierAbonne::where('atelier_id', $atelier->id)
            ->where('gxt_client_id', $user->id)
            ->first();

        if ($ligne && $ligne->actif) {
            // Désabonnement : la ligne est conservée pour la traçabilité (ABO-8).
            $ligne->update(['actif' => false, 'desabonne_at' => now()]);
            $abonne = false;
        } elseif ($ligne) {
            $ligne->update([
                'actif'               => true,
                'desabonne_at'        => null,
                'notifications_optin' => (bool) ($data['notifications_optin'] ?? $ligne->notifications_optin),
            ]);
            $abonne = true;
        } else {
            AtelierAbonne::create([
                'atelier_id'          => $atelier->id,
                'gxt_client_id'       => $user->id,
                'notifications_optin' => (bool) ($data['notifications_optin'] ?? false),
                'actif'               => true,
            ]);
            $abonne = true;
        }

        return response()->json([
            'abonne'  => $abonne,
            'abonnes' => AtelierAbonne::where('atelier_id', $atelier->id)->actifs()->count(),
        ]);
    }

    /**
     * GET /api/vitrine/client/abonnements — mes créateurs suivis (ABO-7).
     * Route authentifiée : permet de consulter et de se désabonner depuis l'espace client.
     */
    /**
     * ABO-5 — PATCH /api/vitrine/client/abonnements/{abonnement}
     *
     * Le consentement aux notifications est DISTINCT de l'abonnement : suivre un
     * créateur ne vaut pas accord pour être notifié. Il ne se réglait jusqu'ici
     * qu'au moment de s'abonner, et ne pouvait plus être changé ensuite — sauf à
     * se désabonner puis se réabonner.
     */
    public function majNotificationsAbonnement(Request $request, AtelierAbonne $abonnement): JsonResponse
    {
        // Un client ne règle que SES propres abonnements.
        if ($abonnement->gxt_client_id !== $request->user()->id) {
            return response()->json(['message' => 'Abonnement introuvable.'], 404);
        }

        $data = $request->validate([
            'notifications_optin' => ['required', 'boolean'],
        ]);

        $abonnement->update(['notifications_optin' => $data['notifications_optin']]);

        return response()->json([
            'notifications_optin' => $abonnement->notifications_optin,
        ]);
    }

    public function mesAbonnements(Request $request): JsonResponse
    {
        $client = $request->user();

        $abonnements = AtelierAbonne::where('gxt_client_id', $client->id)
            ->actifs()
            // logo_url est un accesseur calculé : c'est logo_path qu'il faut sélectionner.
            ->with('atelier:id,nom,logo_path')
            ->latest('created_at')
            ->get()
            ->map(fn ($a) => [
                'id'                  => $a->id,
                'notifications_optin' => $a->notifications_optin,
                'depuis'              => $a->created_at,
                'createur'            => [
                    'id'       => $a->atelier?->id,
                    'nom'      => $a->atelier?->nom,
                    'logo_url' => $a->atelier?->logo_url,
                ],
            ]);

        return response()->json(['abonnements' => $abonnements]);
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
            'abonnes'      => $a->abonnes_count   ?? $a->abonnes()->actifs()->count(),   // P171 👥
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
