<?php

use App\Http\Controllers\Api\Auth\EquipeMembreAuthController;
use App\Http\Controllers\Api\Auth\ProprietaireAuthController;
use App\Http\Controllers\Api\Auth\RecuperationController;
use App\Http\Controllers\Api\AbonnementController;
use App\Http\Controllers\Api\CodePromoController;
use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\AtelierProprietaireController;
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
use App\Http\Controllers\Api\GalerieController;
use App\Http\Controllers\Api\MesureController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ParametresController;
use App\Http\Controllers\Api\PermissionsEquipeController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TicketSupportController;
use App\Http\Controllers\Api\PatronController;
use App\Http\Controllers\Api\PatronPublicController;
use App\Http\Controllers\Api\VetementController;
use App\Http\Controllers\Api\SignalementController;
use App\Http\Controllers\Api\SuiviSprintController;
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
    // Rendu OG côté serveur pour les robots sociaux (proxifié par nginx selon l'User-Agent).
    Route::get('og/createurs/{atelier}', [VitrineController::class, 'ogCreateur']);
    Route::post('createurs/{atelier}/avis',  [AvisController::class, 'store']);
    Route::post('createurs/{atelier}/devis', [DevisController::class, 'store']);
    Route::post('avis/{avis}/signaler',     [AvisController::class, 'signaler']);
    Route::post('createurs/{atelier}/evenement', [VitrineStatsController::class, 'evenement']);
    Route::get('suivi/{reference}',              [VitrineController::class, 'suivi']);
    Route::post('signaler',                      [SignalementController::class, 'store']);
    Route::get('banniere',                       [VitrineController::class, 'banniere']);
    Route::get('sponsorisation',                 [VitrineController::class, 'sponsorisation']);
    Route::get('plans',                          [VitrineController::class, 'plans']);
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
    Route::post('inscription',   [ProprietaireAuthController::class, 'inscription']);
    Route::post('verifier-otp',  [ProprietaireAuthController::class, 'verifierOtp']);
    Route::post('renvoyer-otp',  [ProprietaireAuthController::class, 'renvoyerOtp']);
    // P123 : corriger l'e-mail d'un compte non vérifié (tél + mot de passe) puis renvoi OTP
    Route::post('corriger-email', [ProprietaireAuthController::class, 'corrigerEmail'])
        ->middleware('throttle:5,1');
    Route::post('login',         [ProprietaireAuthController::class, 'login']);
    Route::post('equipe/login',  [EquipeMembreAuthController::class, 'login']);

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

// ─── Routes protégées Sanctum ────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

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
    Route::get('clients',                    [ClientController::class, 'index']);
    Route::post('clients',                   [ClientController::class, 'store']);
    Route::post('clients/import',            [ClientController::class, 'importBatch']);
    Route::get('clients/{client}',           [ClientController::class, 'show']);
    Route::put('clients/{client}',           [ClientController::class, 'update']);
    Route::delete('clients/{client}',        [ClientController::class, 'destroy']);
    Route::post('clients/{client}/archiver',     [ClientController::class, 'archiver']);
    Route::post('clients/{client}/desarchiver',  [ClientController::class, 'desarchiver']);
    Route::post('clients/{client}/toggle-vip',   [ClientController::class, 'toggleVip']);

    // Mesures
    Route::get('clients/{clientId}/mesures',             [MesureController::class, 'index']);
    Route::get('clients/{clientId}/mesures/historique',  [MesureController::class, 'historique']); // P74

    Route::get('clients/{clientId}/mesures/export-csv',  [MesureController::class, 'exportCsv']);
    Route::get('clients/{clientId}/mesures/whatsapp',    [MesureController::class, 'exportWhatsApp']);
    Route::post('mesures',                               [MesureController::class, 'store']);
    Route::put('mesures/{mesure}',                       [MesureController::class, 'update']);
    Route::post('mesures/{mesure}/archiver',             [MesureController::class, 'archiver']);
    Route::post('mesures/{mesure}/desarchiver',          [MesureController::class, 'desarchiver']);
    Route::delete('mesures/{mesure}',                    [MesureController::class, 'destroy']);

    // Clients — recherche globale cross-ateliers
    Route::get('clients/search-global', [ClientController::class, 'searchGlobal']);

    // Commandes
    Route::get('commandes',                               [CommandeController::class, 'index']);
    Route::post('commandes',                              [CommandeController::class, 'store']);
    Route::get('commandes/{commande}',                    [CommandeController::class, 'show']);
    Route::match(['PUT', 'POST'], 'commandes/{commande}', [CommandeController::class, 'update']);
    Route::post('commandes/{commande}/archiver',          [CommandeController::class, 'archiver']);
    Route::post('commandes/{commande}/desarchiver',       [CommandeController::class, 'desarchiver']);
    Route::post('commandes/{commande}/etape',             [CommandeController::class, 'setEtape']);
    Route::delete('commandes/{commande}',                 [CommandeController::class, 'destroy']);

    // Commande items (multi-vêtements par commande)
    Route::get('commandes/{commande}/items',                    [CommandeItemController::class, 'index']);
    Route::post('commandes/{commande}/items',                   [CommandeItemController::class, 'store']);
    Route::put('commandes/{commande}/items/{item}',             [CommandeItemController::class, 'update']);
    Route::delete('commandes/{commande}/items/{item}',          [CommandeItemController::class, 'destroy']);

    // Commande échéances (multi-dates de livraison)
    Route::get('commandes/{commande}/echeances',                [CommandeEcheanceController::class, 'index']);
    Route::post('commandes/{commande}/echeances',               [CommandeEcheanceController::class, 'store']);
    Route::put('commandes/{commande}/echeances/{echeance}',     [CommandeEcheanceController::class, 'update']);
    Route::delete('commandes/{commande}/echeances/{echeance}',  [CommandeEcheanceController::class, 'destroy']);

    // Commandes groupées (un client commande plusieurs types de vêtements en une fois)
    Route::get('commande-groupes',          [CommandeGroupeController::class, 'index']);
    Route::post('commande-groupes',         [CommandeGroupeController::class, 'store']);
    Route::get('commande-groupes/{groupe}', [CommandeGroupeController::class, 'show']);

    // Archives (liste pour le patron)
    Route::get('archives', [ArchiveController::class, 'index']);

    // Caisse (gated par plan module_caisse)
    Route::get('caisse/stats',   [CaisseController::class, 'stats']);
    Route::get('caisse/clients', [CaisseController::class, 'clients']);
    // Paiements de commande
    Route::get('commandes/{commande}/paiements',  [CommandePaiementController::class, 'index']);
    Route::post('commandes/{commande}/paiements', [CommandePaiementController::class, 'store']);

    // Vêtements
    Route::get('vetements',               [VetementController::class, 'index']);
    Route::post('vetements',              [VetementController::class, 'store']);
    Route::match(['PUT', 'POST'], 'vetements/{vetement}', [VetementController::class, 'update']);
    Route::post('vetements/{vetement}/publication', [VetementController::class, 'togglePublication']);
    Route::post('vetements/{vetement}/collection',  [VetementController::class, 'setCollection']);
    Route::delete('vetements/{vetement}', [VetementController::class, 'destroy']);

    // P161-163 : patrons payants (côté créateur) — CRUD + upload fichier privé.
    Route::get('patrons',              [PatronController::class, 'index']);
    Route::post('patrons',             [PatronController::class, 'store']);
    Route::match(['PUT', 'POST'], 'patrons/{patron}', [PatronController::class, 'update']);
    Route::delete('patrons/{patron}',  [PatronController::class, 'destroy']);

    // Collections (regroupement de créations)
    Route::get('collections',                 [CollectionController::class, 'index']);
    Route::post('collections',                [CollectionController::class, 'store']);
    Route::put('collections/{collection}',    [CollectionController::class, 'update']);
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
    Route::get('abonnement/plans',         [AbonnementController::class, 'plans']);
    Route::get('abonnement/current',       [AbonnementController::class, 'current']);
    Route::post('abonnement/activer-code', [AbonnementController::class, 'activerCode']);
    Route::post('abonnement/sponsoriser',  [AbonnementController::class, 'sponsoriser']);

    // Codes promo / ambassadeurs (P153-158) — rate-limité (anti brute-force)
    Route::post('codes-promo/utiliser', [CodePromoController::class, 'utiliser'])
        ->middleware('throttle:5,1');

    // Notifications
    Route::get('notifications',                   [NotificationController::class, 'index']);
    Route::post('notifications/mark-as-read',     [NotificationController::class, 'markAsRead']);
    Route::post('notifications/delete',           [NotificationController::class, 'destroy']);
    Route::post('notifications/fcm-token',        [NotificationController::class, 'registerFcmToken']);
    Route::delete('notifications/fcm-token',      [NotificationController::class, 'removeFcmToken']);

    // Tickets support (propriétaire)
    Route::get('support/tickets',              [TicketSupportController::class, 'index']);
    Route::post('support/tickets',             [TicketSupportController::class, 'store']);
    Route::get('support/tickets/{id}',         [TicketSupportController::class, 'show']);
    Route::post('support/tickets/{id}/repondre', [TicketSupportController::class, 'repondre']);

    // Paramètres
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
    Route::post('factures',                   [FactureController::class, 'store']);
    Route::get('factures/{facture}',          [FactureController::class, 'show']);
    Route::patch('factures/{facture}/statut', [FactureController::class, 'updateStatut']);
    Route::post('factures/{facture}/dgi',     [FactureController::class, 'uploadDgi']);
    Route::get('factures/{facture}/dgi',      [FactureController::class, 'downloadDgi']);
    Route::post('factures/{facture}/normaliser', [FactureController::class, 'normaliser']);
    Route::delete('factures/{facture}',       [FactureController::class, 'destroy']);

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

    // Galerie photos VIP
    Route::get('galerie',              [GalerieController::class, 'index']);
    Route::post('galerie',             [GalerieController::class, 'store']);
    Route::delete('galerie/{photo}',   [GalerieController::class, 'destroy']);
    Route::get('galerie/quota',        [GalerieController::class, 'quota']);
});

// ─── Webhooks (pas d'auth) ───────────────────────────────────────────────────
Route::post('webhooks/{provider}', [WebhookController::class, 'handle']);
