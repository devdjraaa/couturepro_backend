<?php

use App\Http\Controllers\Api\Auth\EquipeMembreAuthController;
use App\Http\Controllers\Api\Auth\ProprietaireAuthController;
use App\Http\Controllers\Api\Auth\RecuperationController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\AbonnementController;
use App\Http\Controllers\Api\CodePromoController;
use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\AtelierProprietaireController;
use App\Http\Controllers\Api\AtelierVideoController;
use App\Http\Controllers\Api\AvisController;
use App\Http\Controllers\Api\CaisseController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\CommandeEcheanceController;
use App\Http\Controllers\Api\CommandeGroupeController;
use App\Http\Controllers\Api\CommandeItemController;
use App\Http\Controllers\Api\CommandePaiementController;
use App\Http\Controllers\Api\CreationDesignerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DevisController;
use App\Http\Controllers\Api\FactureController;
use App\Http\Controllers\Api\EquipeMembreController;
use App\Http\Controllers\Api\FideliteController;
use App\Http\Controllers\Api\AnnonceController;
use App\Http\Controllers\Api\GalerieController;
use App\Http\Controllers\Api\RealisationController;
use App\Http\Controllers\Api\ListeAttenteController;
use App\Http\Controllers\Api\MesureController;
use App\Http\Controllers\Api\InfosController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ParametresController;
use App\Http\Controllers\Api\PermissionsEquipeController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TicketSupportController;
use App\Http\Controllers\Api\PatronController;
use App\Http\Controllers\Api\PartenairePublicController;
use App\Http\Controllers\Api\PatronPublicController;
use App\Http\Controllers\Api\VetementController;
use App\Http\Controllers\Api\SignalementController;
use App\Http\Controllers\Api\SuiviSprintController;
use App\Http\Controllers\Api\Vitrine\ChatbotController;
use App\Http\Controllers\Api\Vitrine\ClientAuthController;
use App\Http\Controllers\Api\Vitrine\ClientCommandeController;
use App\Http\Controllers\Api\Vitrine\ClientNotificationController;
use App\Http\Controllers\Api\Vitrine\GxtEvenementController;
use App\Http\Controllers\Api\VitrineController;
use App\Http\Controllers\Api\VitrineStatsController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WhatsAppController;
use Illuminate\Support\Facades\Route;

// ─── Route d'entrée ─────────────────────────────────────────────────────────
Route::get('/', fn() => response()->json([
    'app'     => config('app.name'),
    'version' => '1.0.0',
    'status'  => 'ok',
]));

// ─── Vitrine publique (marketplace, sans authentification) ───────────────────
// P199 : sitemap dynamique des créateurs (référencé dans robots.txt de la vitrine).
Route::get('sitemap/createurs.xml', [\App\Http\Controllers\Api\SitemapController::class, 'createurs']);

Route::prefix('vitrine')->group(function () {
    Route::get('createurs',            [VitrineController::class, 'index']);
    Route::get('createurs/{atelier}',  [VitrineController::class, 'show']);
    // Catalogue public (galerie de l'accueil vitrine) — alimente getCreations() côté front.
    Route::get('creations',            [VitrineController::class, 'creations']);
    // P159-160 : like public d'une création (anonyme, dé-doublonné par visitor_key).
    Route::post('creations/{vetement}/like', [VitrineController::class, 'toggleLike']);
    // P173 : s'abonner / se désabonner d'un créateur (anonyme).
    Route::post('createurs/{atelier}/abonnement', [VitrineController::class, 'toggleAbonnement']);
    // P161-163 : patrons payants — achat (→ paiement), récupération par code, téléchargement.
    Route::post('patrons/{patron}/acheter',              [PatronPublicController::class, 'acheter']);
    Route::get('patrons/achats/{code}',                  [PatronPublicController::class, 'statut']);
    Route::get('patrons/achats/{code}/telecharger',      [PatronPublicController::class, 'telecharger']);
    // REL-2 / Pt 125 : pré-rendu HTML pour TOUS les robots (nginx route les
    // User-Agents de robots vers cette page ; les humains n'y passent jamais).
    Route::get('seo/render', [\App\Http\Controllers\Api\SeoRenderController::class, 'render']);

    // Rendu OG côté serveur pour les robots sociaux (proxifié par nginx selon l'User-Agent).
    Route::get('og/createurs/{atelier}', [VitrineController::class, 'ogCreateur']);
    // S08C-29 : routes publiques d'avis — limitées en débit (aucune limitation
    // auparavant : dépôt d'avis et signalements pouvaient être envoyés en boucle).
    Route::post('createurs/{atelier}/avis',  [AvisController::class, 'store'])->middleware('throttle:5,60'); // 410 — remplacé par le dépôt par modèle
    // Avis v2 (décisions 20/07) : dépôt sur un MODÈLE, compte client obligatoire.
    Route::post('creations/{vetement}/avis', [AvisController::class, 'storePourModele'])->middleware('throttle:5,60');
    // VASAT (produit masqué) : accès par mot de passe, anti force brute.
    Route::post('vasat/acces', [VitrineController::class, 'accesVasat'])->middleware('throttle:10,60');
    Route::post('createurs/{atelier}/devis', [DevisController::class, 'store']);
    Route::post('avis/{avis}/signaler',     [AvisController::class, 'signaler'])->middleware('throttle:10,60');
    Route::post('createurs/{atelier}/evenement', [VitrineStatsController::class, 'evenement']);
    Route::get('suivi/{reference}',              [VitrineController::class, 'suivi']);
    Route::post('signaler',                      [SignalementController::class, 'store']);
    Route::get('banniere',                       [VitrineController::class, 'banniere']);
    // Brief 16/07 (pt 6) : habillage saisonnier local (overlay d'ouverture, config admin).
    Route::get('splash-theme',                   [VitrineController::class, 'splashTheme']);
    // Point 57 : événements dynamiques du jour (auth optionnelle pour l'anniversaire perso).
    Route::get('evenements',                     [VitrineController::class, 'evenements']);
    // ANN-8 : annonces en cours de diffusion (bande défilante), boostées d'abord.
    Route::get('annonces',                       [VitrineController::class, 'annonces']);
    Route::post('annonces/{annonce}/signaler',   [VitrineController::class, 'signalerAnnonce'])->middleware('throttle:10,60');
    Route::get('sponsorisation',                 [VitrineController::class, 'sponsorisation']);
    Route::get('plans',                          [VitrineController::class, 'plans']);
    // P204 : partenaires (liste par catégorie + bandeau accueil + candidature)
    Route::get('partenaires',                    [PartenairePublicController::class, 'index']);
    Route::get('partenaires/cles',               [PartenairePublicController::class, 'cles']);
    Route::post('partenaires/candidature',       [PartenairePublicController::class, 'candidater']);
});

// ─── Tracking métier vitrine (P202 Phase 3) : ingestion groupée, connecté ou anonyme ───
Route::post('vitrine/evenements', [GxtEvenementController::class, 'ingest'])->middleware('throttle:60,1');

// ─── Pages légales éditables (footer vitrine) : lecture publique ───
Route::get('vitrine/pages/{cle}', [\App\Http\Controllers\Api\PageLegaleController::class, 'show']);

// Coordonnées officielles (marque, site, téléphone) : source unique pour les
// documents et partages sortants — le front ne doit rien figer en dur.
Route::get('vitrine/coordonnees', fn () => response()->json(\App\Models\VitrineSetting::coordonnees()));
Route::get('vitrine/types-document', fn () => response()->json(['types' => \App\Models\VitrineSetting::typesDocumentVerification()]));

// Identité légale (RCCM, IFU, délibération APDP, dates d'entrée en vigueur) :
// injectée dans les 11 pages juridiques. Publique par nature — ces mentions ont
// vocation à être lues par n'importe quel visiteur.
Route::get('vitrine/identite-legale', fn () => response()->json(\App\Models\VitrineSetting::identiteLegale()));

// CLI-3 : compte à rebours de lancement. Public — il s'affiche avant même
// qu'un visiteur ait un compte.
Route::get('vitrine/compte-a-rebours', fn () => response()->json(\App\Models\VitrineSetting::compteARebours()));

// CLI-1 : catégories de la bibliothèque photos, éditables sans déploiement.
Route::get('vitrine/categories-galerie', fn () => response()->json(['categories' => \App\Models\VitrineSetting::categoriesGalerie()]));

// CLI-1 : référentiel des devises (symbole, décimales). Public : les montants
// s'affichent aussi sur la vitrine, avant toute connexion. Le front n'a ainsi
// aucune correspondance devise → symbole en dur à maintenir.
Route::get('vitrine/devises', fn () => response()->json(app(\App\Services\DeviseService::class)->referentiel()));

// CLI-1 : journal des mises à jour (« Quoi de neuf »). Public : la liste des
// nouveautés n'a rien de confidentiel, et l'écran doit rester lisible même
// quand la session a expiré.
Route::get('app/journal-maj', fn () => response()->json(['entrees' => \App\Models\VitrineSetting::journalMaj()]));

// Lot 2 (20/07) : désinscription des actualités depuis le lien d'un e-mail.
// URL SIGNÉE : elle doit fonctionner sans connexion (on ne demande pas à
// quelqu'un de se connecter pour arrêter de recevoir des messages), tout en
// empêchant de désinscrire un tiers en devinant son identifiant.
Route::get('vitrine/desinscription/{client}', function (\App\Models\GxtClient $client) {
    $client->definirNewsletter(false);

    return response()->json([
        'message' => 'Vous ne recevrez plus nos actualités. Votre compte reste actif.',
    ]);
})->name('vitrine.desinscription')->middleware('signed');

// ─── Veille n8n : dépôt des résultats hebdo (jeton partagé X-Veille-Token) ───
Route::post('veille/ingest', [\App\Http\Controllers\Api\VeilleController::class, 'ingest'])->middleware('throttle:10,1');

// ─── Chatbot vitrine (brief 16/07 pt 1) : assistance + mémoire des échanges ───
Route::prefix('vitrine/chatbot')->group(function () {
    Route::post('message',    [ChatbotController::class, 'message'])->middleware('throttle:20,1');
    Route::post('feedback',   [ChatbotController::class, 'feedback'])->middleware('throttle:30,1');
    Route::get('historique',  [ChatbotController::class, 'historique'])->middleware('throttle:30,1');
    // Badge « équipe hors ligne » (20/07) — Makila reste actif 24h/24.
    Route::get('statut',      [ChatbotController::class, 'statut'])->middleware('throttle:60,1');
});

// ─── Espace client vitrine (P202) : auth sans mot de passe (Google / OTP e-mail) ───
Route::prefix('vitrine/client')->group(function () {
    Route::post('otp/demander', [ClientAuthController::class, 'demanderOtp'])->middleware('throttle:5,1');
    Route::post('otp/verifier', [ClientAuthController::class, 'verifierOtp'])->middleware('throttle:10,1');
    Route::post('google',       [ClientAuthController::class, 'google'])->middleware('throttle:10,1');

    Route::middleware(['auth:sanctum', 'account:client'])->group(function () {
        Route::get('me',            [ClientAuthController::class, 'me']);
        Route::patch('me',          [ClientAuthController::class, 'majProfil']); // brief 16/07 pt 3
        Route::post('consentement', [ClientAuthController::class, 'consentement']);
        // ABO-7 : mes créateurs suivis (consultation + désabonnement via le toggle).
        Route::get('abonnements',   [VitrineController::class, 'mesAbonnements']);
        // ABO-5 : le consentement aux notifications se règle indépendamment de
        // l'abonnement (il ne pouvait plus changer une fois l'abonnement créé).
        Route::patch('abonnements/{abonnement}', [VitrineController::class, 'majNotificationsAbonnement']);
        Route::post('logout',       [ClientAuthController::class, 'logout']);

        // Phase 2 : commandes « direct » (atterrissent dans l'outil du designer) + avis + réclamations.
        Route::get('commandes',                             [ClientCommandeController::class, 'index']);
        Route::post('commandes',                            [ClientCommandeController::class, 'store'])->middleware('throttle:10,60');
        Route::post('commandes/{commande}/avis',            [ClientCommandeController::class, 'avis'])->middleware('throttle:5,60');
        Route::post('commandes/{commande}/reclamation',     [ClientCommandeController::class, 'reclamation'])->middleware('throttle:5,60');

        // Pt 24 — notifications du client final. Il n'était prévenu que par
        // e-mail : un e-mail se perd ou part en indésirable, et le client
        // revenait alors sur son espace sans rien y trouver sur sa commande.
        Route::get('notifications',                    [ClientNotificationController::class, 'index']);
        Route::get('notifications/compteur',           [ClientNotificationController::class, 'compteur']);
        Route::post('notifications/tout-lu',           [ClientNotificationController::class, 'toutMarquerLu']);
        Route::post('notifications/{notification}/lue', [ClientNotificationController::class, 'marquerLue']);
    });
});

// ─── Mises à jour de l'app (version-gate natif + OTA bundle web) ─────────────
Route::prefix('app')->group(function () {
    Route::get('version',  [AppVersionController::class, 'version']);   // grosse MAJ (APK)
    Route::post('updates', [AppVersionController::class, 'updates']);   // OTA self-hosted (Capgo)
});

// ─── Suivi des sprints (état partagé public ; écriture protégée par code) ─────
Route::get('suivi-sprints',  [SuiviSprintController::class, 'show']);
Route::post('suivi-sprints', [SuiviSprintController::class, 'save']);

// ─── Auth publique ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('inscription',   [ProprietaireAuthController::class, 'inscription'])->middleware('recaptcha'); // P196
    Route::post('verifier-otp',  [ProprietaireAuthController::class, 'verifierOtp']);
    Route::post('renvoyer-otp',  [ProprietaireAuthController::class, 'renvoyerOtp']);
    // P123 : corriger l'e-mail d'un compte non vérifié (tél + mot de passe) puis renvoi OTP
    Route::post('corriger-email', [ProprietaireAuthController::class, 'corrigerEmail'])
        ->middleware('throttle:5,1');
    Route::post('login',         [ProprietaireAuthController::class, 'login']);
    Route::post('equipe/login',  [EquipeMembreAuthController::class, 'login']);

    // P150 : connexion sociale (Google/Facebook/Apple) — actifs selon les clés .env.
    Route::get('social/providers',          [SocialAuthController::class, 'providers']);
    Route::get('social/{provider}/redirect', [SocialAuthController::class, 'redirect']);
    Route::get('social/{provider}/callback', [SocialAuthController::class, 'callback']);
    // Flux natif (app mobile) : le plugin fournit un idToken, vérifié serveur-à-serveur.
    Route::post('social/{provider}/token',   [SocialAuthController::class, 'tokenLogin'])
        ->middleware('throttle:10,1');

    Route::prefix('recuperation')->group(function () {
        Route::post('initier',              [RecuperationController::class, 'etape1']);
        Route::post('verifier-otp',         [RecuperationController::class, 'etape2']);
        Route::post('nouveau-telephone',    [RecuperationController::class, 'etape3']);
        Route::post('verifier-otp-nouveau', [RecuperationController::class, 'etape4']);
        Route::post('nouveau-mot-de-passe', [RecuperationController::class, 'etape5']);

        // Recovery via question secrète : récupère la question puis valide la réponse
        // → retourne un token directement (pas de changement de mot de passe forcé)
        Route::post('question/lire',     [RecuperationController::class, 'lireQuestionSecrete']);
        Route::post('question/verifier', [RecuperationController::class, 'verifierQuestionSecrete']);
    });
});

// ─── Routes protégées Sanctum (espace pro : Proprietaire + EquipeMembre) ─────
// account:app = interdit aux jetons « client vitrine » (P202) d'atteindre l'espace pro.
Route::middleware(['auth:sanctum', 'account:app'])->group(function () {

    // Auth
    Route::post('auth/logout', [ProprietaireAuthController::class, 'logout']);
    Route::get('auth/me',      [ProprietaireAuthController::class, 'me']);

    // Dashboard
    Route::get('dashboard',       [DashboardController::class, 'index']);
    Route::get('dashboard/multi', [DashboardController::class, 'multi']);

    // Multi-ateliers (propriétaire seulement — EquipeMembre bloqué côté controller)
    Route::get('ateliers/mes-ateliers',                    [AtelierProprietaireController::class, 'mesAteliers']);
    Route::post('ateliers',                                [AtelierProprietaireController::class, 'store']);
    Route::get('ateliers/{atelierIdParam}/stats',          [AtelierProprietaireController::class, 'stats']);
    Route::post('ateliers/sync-config',                    [AtelierProprietaireController::class, 'syncConfig']);
    Route::post('ateliers/downgrade-check',                [AtelierProprietaireController::class, 'downgradeCheck']);
    Route::post('ateliers/{atelierIdParam}/deverrouiller', [AtelierProprietaireController::class, 'deverrouiller']);

    // Clients
    Route::get('clients',                    [ClientController::class, 'index'])->middleware('equipe.permission:clients.view');
    Route::post('clients',                   [ClientController::class, 'store'])->middleware('equipe.permission:clients.create');
    Route::post('clients/import',            [ClientController::class, 'importBatch'])->middleware('equipe.permission:clients.create');
    Route::get('clients/{client}',           [ClientController::class, 'show'])->middleware('equipe.permission:clients.view');
    Route::put('clients/{client}',           [ClientController::class, 'update'])->middleware('equipe.permission:clients.edit');
    Route::delete('clients/{client}',        [ClientController::class, 'destroy'])->middleware('equipe.permission:clients.delete');
    Route::post('clients/{client}/archiver',     [ClientController::class, 'archiver'])->middleware('equipe.permission:clients.archive');
    Route::post('clients/{client}/desarchiver',  [ClientController::class, 'desarchiver'])->middleware('equipe.permission:clients.archive');
    Route::post('clients/{client}/toggle-vip',   [ClientController::class, 'toggleVip'])->middleware('equipe.permission:clients.edit');

    // Mesures
    Route::get('mesures/export-groupe',                  [MesureController::class, 'exportGroupe'])->middleware('equipe.permission:mesures.view'); // PL-2
    Route::get('clients/{clientId}/mesures',             [MesureController::class, 'index'])->middleware('equipe.permission:mesures.view');
    Route::get('clients/{clientId}/mesures/historique',  [MesureController::class, 'historique'])->middleware('equipe.permission:mesures.view'); // P74

    Route::get('clients/{clientId}/mesures/export-csv',  [MesureController::class, 'exportCsv'])->middleware('equipe.permission:mesures.view');
    Route::get('clients/{clientId}/mesures/whatsapp',    [MesureController::class, 'exportWhatsApp'])->middleware('equipe.permission:mesures.view');
    Route::post('mesures',                               [MesureController::class, 'store'])->middleware('equipe.permission:mesures.edit');
    Route::put('mesures/{mesure}',                       [MesureController::class, 'update'])->middleware('equipe.permission:mesures.edit');
    Route::post('mesures/{mesure}/archiver',             [MesureController::class, 'archiver'])->middleware('equipe.permission:mesures.archive');
    Route::post('mesures/{mesure}/desarchiver',          [MesureController::class, 'desarchiver'])->middleware('equipe.permission:mesures.archive');
    Route::delete('mesures/{mesure}',                    [MesureController::class, 'destroy'])->middleware('equipe.permission:mesures.archive');

    // Clients — recherche globale cross-ateliers
    Route::get('clients/search-global', [ClientController::class, 'searchGlobal'])->middleware('equipe.permission:clients.view');

    // Commandes
    Route::get('commandes',                               [CommandeController::class, 'index'])->middleware('equipe.permission:commandes.view');
    Route::post('commandes',                              [CommandeController::class, 'store'])->middleware('equipe.permission:commandes.create');
    Route::get('commandes/{commande}',                    [CommandeController::class, 'show'])->middleware('equipe.permission:commandes.view');
    Route::match(['PUT', 'POST'], 'commandes/{commande}', [CommandeController::class, 'update'])->middleware('equipe.permission:commandes.edit');
    Route::post('commandes/{commande}/archiver',          [CommandeController::class, 'archiver'])->middleware('equipe.permission:commandes.archive');
    Route::post('commandes/{commande}/desarchiver',       [CommandeController::class, 'desarchiver'])->middleware('equipe.permission:commandes.archive');
    Route::post('commandes/{commande}/etape',             [CommandeController::class, 'setEtape'])->middleware('equipe.permission:commandes.edit');
    Route::delete('commandes/{commande}',                 [CommandeController::class, 'destroy'])->middleware('equipe.permission:commandes.delete');

    // Commande items (multi-vêtements par commande)
    Route::get('commandes/{commande}/items',                    [CommandeItemController::class, 'index'])->middleware('equipe.permission:commandes.view');
    Route::post('commandes/{commande}/items',                   [CommandeItemController::class, 'store'])->middleware('equipe.permission:commandes.edit');
    Route::put('commandes/{commande}/items/{item}',             [CommandeItemController::class, 'update'])->middleware('equipe.permission:commandes.edit');
    Route::delete('commandes/{commande}/items/{item}',          [CommandeItemController::class, 'destroy'])->middleware('equipe.permission:commandes.edit');

    // Commande échéances (multi-dates de livraison)
    Route::get('commandes/{commande}/echeances',                [CommandeEcheanceController::class, 'index'])->middleware('equipe.permission:commandes.view');
    Route::post('commandes/{commande}/echeances',               [CommandeEcheanceController::class, 'store'])->middleware('equipe.permission:commandes.edit');
    Route::put('commandes/{commande}/echeances/{echeance}',     [CommandeEcheanceController::class, 'update'])->middleware('equipe.permission:commandes.edit');
    Route::delete('commandes/{commande}/echeances/{echeance}',  [CommandeEcheanceController::class, 'destroy'])->middleware('equipe.permission:commandes.edit');

    // Commandes groupées (un client commande plusieurs types de vêtements en une fois)
    Route::get('commande-groupes',          [CommandeGroupeController::class, 'index'])->middleware('equipe.permission:commandes.view');
    Route::post('commande-groupes',         [CommandeGroupeController::class, 'store'])->middleware('equipe.permission:commandes.create');
    Route::get('commande-groupes/{groupe}', [CommandeGroupeController::class, 'show'])->middleware('equipe.permission:commandes.view');

    // Archives (liste pour le patron)
    Route::get('archives', [ArchiveController::class, 'index']);

    // Caisse (gated par plan module_caisse)
    Route::get('caisse/stats',   [CaisseController::class, 'stats']);
    Route::get('caisse/clients', [CaisseController::class, 'clients']);
    Route::get('caisse/rapport-mensuel', [CaisseController::class, 'rapportMensuel']); // PL-3
    // Paiements de commande
    Route::get('commandes/{commande}/paiements',  [CommandePaiementController::class, 'index'])->middleware('equipe.permission:paiements.view');
    Route::post('commandes/{commande}/paiements', [CommandePaiementController::class, 'store'])->middleware('equipe.permission:paiements.create');

    // Vêtements
    Route::get('vetements',               [VetementController::class, 'index']);
    Route::post('vetements',              [VetementController::class, 'store'])->middleware('equipe.permission:vetements.manage');
    Route::match(['PUT', 'POST'], 'vetements/{vetement}', [VetementController::class, 'update'])->middleware('equipe.permission:vetements.manage');
    Route::post('vetements/{vetement}/publication', [VetementController::class, 'togglePublication'])->middleware('equipe.permission:vetements.manage');
    Route::post('vetements/{vetement}/collection',  [VetementController::class, 'setCollection'])->middleware('equipe.permission:vetements.manage');
    Route::delete('vetements/{vetement}', [VetementController::class, 'destroy'])->middleware('equipe.permission:vetements.manage');

    // P161-163 : patrons payants (côté créateur) — CRUD + upload fichier privé.
    Route::get('patrons',              [PatronController::class, 'index']);
    Route::post('patrons',             [PatronController::class, 'store']);
    Route::match(['PUT', 'POST'], 'patrons/{patron}', [PatronController::class, 'update']);
    Route::delete('patrons/{patron}',  [PatronController::class, 'destroy']);

    // Collections (regroupement de créations)
    Route::get('collections',                 [CollectionController::class, 'index']);
    Route::post('collections',                [CollectionController::class, 'store']);
    Route::put('collections/{collection}',    [CollectionController::class, 'update']);
    Route::post('collections/{collection}/annonce', [CollectionController::class, 'annoncer']); // PL-6
    Route::delete('collections/{collection}', [CollectionController::class, 'destroy']);

    // Avis (modération par le créateur)
    Route::get('avis',                    [AvisController::class, 'index']);
    Route::post('avis/{avis}/moderation', [AvisController::class, 'moderer']);
    Route::get('vitrine-stats',           [VitrineStatsController::class, 'mesStats']);

    // Demandes de devis (reçues par le créateur)
    Route::get('devis',                   [DevisController::class, 'index']);
    Route::post('devis/{devis}/traiter',  [DevisController::class, 'traiter']);

    // Équipe
    Route::get('equipe',                    [EquipeMembreController::class, 'index']);
    Route::post('equipe',                   [EquipeMembreController::class, 'store']);
    Route::delete('equipe/{membre}',        [EquipeMembreController::class, 'destroy']);
    Route::get('equipe/permissions',        [PermissionsEquipeController::class, 'index']);
    Route::put('equipe/permissions',        [PermissionsEquipeController::class, 'update']);

    // Sync
    Route::post('sync/push', [SyncController::class, 'push']);
    Route::get('sync/pull',  [SyncController::class, 'pull']);

    // Fidélité
    Route::get('fidelite',            [FideliteController::class, 'show']);
    Route::post('fidelite/convertir', [FideliteController::class, 'convertir']);

    // Paiements abonnement (FedaPay)
    Route::post('paiements/initier',          [PaiementController::class, 'initier']);
    Route::get('paiements/retour',            [PaiementController::class, 'verifierRetour']);
    Route::get('paiements/{paiement}/status', [PaiementController::class, 'status']);

    // Abonnement
    Route::get('abonnement/plans',           [AbonnementController::class, 'plans']);
    Route::get('abonnement/current',         [AbonnementController::class, 'current']);
    Route::get('abonnement/upgrade-preview', [AbonnementController::class, 'upgradePreview']);
    Route::post('abonnement/programmer-downgrade', [AbonnementController::class, 'programmerDowngrade']); // P53-55
    Route::post('abonnement/annuler-downgrade',    [AbonnementController::class, 'annulerDowngrade']);
    Route::post('abonnement/activer-code', [AbonnementController::class, 'activerCode']);
    Route::post('abonnement/sponsoriser',  [AbonnementController::class, 'sponsoriser']);

    // Codes promo / ambassadeurs (P153-158) — rate-limité (anti brute-force)
    Route::post('codes-promo/utiliser', [CodePromoController::class, 'utiliser'])
        ->middleware('throttle:5,1');

    // Notifications
    Route::get('notifications',                   [NotificationController::class, 'index'])->middleware('equipe.permission:notifications.view');

    // CLI-2 — « Gextimo Infos » : onglet distinct des notifications. Une
    // notification concerne VOTRE atelier et appelle une action ; une info est
    // un message éditorial de Gextimo. Les mélanger noie les alertes.
    //
    // Volontairement SANS `equipe.permission` — contrairement aux notifications
    // juste au-dessus. Une info est une communication de Gextimo à toute la
    // communauté : annonces, formations, alertes de sécurité. La soumettre à
    // une permission d'équipe reviendrait à cacher une alerte de sécurité aux
    // employés d'un atelier, c'est-à-dire précisément aux personnes visées.
    Route::get('infos',                 [InfosController::class, 'index']);
    Route::get('infos/compteur',        [InfosController::class, 'compteur']);
    Route::post('infos/tout-lu',        [InfosController::class, 'toutMarquerLu']);
    Route::post('infos/{info}/lue',     [InfosController::class, 'marquerLue']);
    Route::post('notifications/mark-as-read',     [NotificationController::class, 'markAsRead'])->middleware('equipe.permission:notifications.view');
    Route::post('notifications/delete',           [NotificationController::class, 'destroy'])->middleware('equipe.permission:notifications.view');
    Route::post('notifications/fcm-token',        [NotificationController::class, 'registerFcmToken']);
    Route::delete('notifications/fcm-token',      [NotificationController::class, 'removeFcmToken']);

    // Tickets support (propriétaire)
    Route::get('support/tickets',              [TicketSupportController::class, 'index']);
    Route::post('support/tickets',             [TicketSupportController::class, 'store']);
    Route::get('support/tickets/{id}',         [TicketSupportController::class, 'show']);
    Route::post('support/tickets/{id}/repondre', [TicketSupportController::class, 'repondre']);

    // Paramètres
    // S08C-30 : moyens de paiement de la facturation (source unique, éditable en admin)
    Route::get('moyens-paiement',                [VitrineController::class, 'moyensPaiement']);
    Route::put('parametres/profil',              [ParametresController::class, 'updateProfil']);
    Route::put('parametres/atelier',             [ParametresController::class, 'updateAtelier']);
    Route::post('parametres/atelier/logo',       [ParametresController::class, 'uploadAtelierLogo']);
    // P134 : bannière du profil créateur (photo/GIF/vidéo).
    Route::post('parametres/atelier/banniere',   [ParametresController::class, 'uploadAtelierBanniere']);
    Route::delete('parametres/atelier/banniere', [ParametresController::class, 'supprimerAtelierBanniere']);
    Route::post('parametres/demande-verification', [ParametresController::class, 'demanderVerification']);
    Route::get('parametres/communications',      [ParametresController::class, 'getCommunications']);
    Route::put('parametres/communications',      [ParametresController::class, 'updateCommunications']);
    Route::put('parametres/mot-de-passe',        [ParametresController::class, 'changerMotDePasse']);
    Route::get('parametres/preferences',         [ParametresController::class, 'getPreferences']);
    Route::put('parametres/preferences',         [ParametresController::class, 'updatePreferences']);
    Route::get('parametres/preferences/complet', [ParametresController::class, 'getPreferencesComplet']);
    Route::get('parametres/langue',              [ParametresController::class, 'getLangue']);
    Route::put('parametres/langue',              [ParametresController::class, 'updateLangue']);
    Route::get('parametres/facture',             [ParametresController::class, 'getFacture']);
    Route::put('parametres/facture',             [ParametresController::class, 'updateFacture']);
    Route::post('parametres/facture/logo',       [ParametresController::class, 'uploadFactureLogo']);
    Route::put('parametres/type-compte',        [ParametresController::class, 'changerTypeCompte']);

    // Facturation designer (devis / factures / reçus)
    Route::get('factures',                    [FactureController::class, 'index']);
    Route::post('factures',                   [FactureController::class, 'store'])->middleware('equipe.permission:factures.generate');
    Route::get('factures/{facture}',          [FactureController::class, 'show']);
    Route::patch('factures/{facture}/statut', [FactureController::class, 'updateStatut'])->middleware('equipe.permission:factures.generate');
    Route::post('factures/{facture}/dgi',     [FactureController::class, 'uploadDgi'])->middleware('equipe.permission:factures.generate');
    Route::get('factures/{facture}/dgi',      [FactureController::class, 'downloadDgi']);
    Route::post('factures/{facture}/normaliser', [FactureController::class, 'normaliser'])->middleware('equipe.permission:factures.generate');
    Route::delete('factures/{facture}',       [FactureController::class, 'destroy'])->middleware('equipe.permission:factures.generate');

    // WhatsApp
    Route::get('whatsapp/rappel-client/{clientId}',            [WhatsAppController::class, 'rappelClient']);
    Route::get('whatsapp/confirmation-commande/{commandeId}',  [WhatsAppController::class, 'confirmationCommande']);
    Route::get('whatsapp/commande-prete/{commandeId}',         [WhatsAppController::class, 'commandePrete']);
    Route::get('whatsapp/preuve-paiement/{commandeId}',        [WhatsAppController::class, 'preuvePaiement']);

    // Outils créatifs designer (croquis, fiches techniques, patrons, moodboards)
    Route::get('creations-designer',                       [CreationDesignerController::class, 'index']);
    Route::post('creations-designer',                      [CreationDesignerController::class, 'store']);
    Route::get('creations-designer/{creation}',            [CreationDesignerController::class, 'show']);
    Route::match(['PUT', 'POST'], 'creations-designer/{creation}', [CreationDesignerController::class, 'update']);
    Route::delete('creations-designer/{creation}',         [CreationDesignerController::class, 'destroy']);

    // PL-4 : liste d'attente clients (Studio)
    Route::get('liste-attente',                [ListeAttenteController::class, 'index']);
    Route::post('liste-attente',               [ListeAttenteController::class, 'store']);
    Route::put('liste-attente/{liste_attente}', [ListeAttenteController::class, 'update']);
    Route::delete('liste-attente/{liste_attente}', [ListeAttenteController::class, 'destroy']);

    // PL-7 : vidéos de présentation (Studio)
    // VID-2 : compteur de vidéos selon le plan (0/1, 2/3, 5/5) — avant la route {atelier_video}.
    Route::get('atelier-videos/quota',             [AtelierVideoController::class, 'quota']);
    Route::get('atelier-videos',                   [AtelierVideoController::class, 'index']);
    Route::post('atelier-videos',                  [AtelierVideoController::class, 'store']);
    // VID-3 : correction d'une vidéo (plafonnée par mois selon le plan)
    Route::put('atelier-videos/{atelier_video}',    [AtelierVideoController::class, 'update']);
    Route::delete('atelier-videos/{atelier_video}', [AtelierVideoController::class, 'destroy']);

    // Galerie photos VIP
    Route::get('galerie',              [GalerieController::class, 'index']);
    Route::post('galerie',             [GalerieController::class, 'store']);
    Route::delete('galerie/{photo}',   [GalerieController::class, 'destroy']);
    Route::get('galerie/quota',        [GalerieController::class, 'quota']);

    // ANN-1..9 : module Annonces (Espace Designer) — publication gratuite, 1/jour
    Route::get('annonces/quota',                [AnnonceController::class, 'quota']);
    Route::get('annonces',                      [AnnonceController::class, 'index']);
    Route::post('annonces',                     [AnnonceController::class, 'store']);
    Route::put('annonces/{annonce}',            [AnnonceController::class, 'update']);
    Route::post('annonces/{annonce}/image',     [AnnonceController::class, 'image']);
    Route::post('annonces/{annonce}/boost',     [AnnonceController::class, 'boost'])->middleware('throttle:10,60');
    Route::delete('annonces/{annonce}/image',   [AnnonceController::class, 'retirerImage']);
    Route::delete('annonces/{annonce}',         [AnnonceController::class, 'destroy']);

    // Point 101 : Mes Réalisations (publication modérée de photos)
    Route::get('realisations/quota',                    [RealisationController::class, 'quota']);
    Route::get('realisations',                          [RealisationController::class, 'index']);
    Route::post('realisations',                         [RealisationController::class, 'store']);
    Route::put('realisations/{realisation}',            [RealisationController::class, 'update']);
    Route::post('realisations/{realisation}/photo',     [RealisationController::class, 'ajouterPhoto']);
    Route::delete('realisations/{realisation}/photo',   [RealisationController::class, 'retirerPhoto']);
    Route::post('realisations/{realisation}/soumettre', [RealisationController::class, 'soumettre']);
    Route::delete('realisations/{realisation}',         [RealisationController::class, 'destroy']);
});

// ─── Webhooks (pas d'auth) ───────────────────────────────────────────────────
Route::post('webhooks/{provider}', [WebhookController::class, 'handle']);
