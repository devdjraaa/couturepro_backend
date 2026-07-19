<?php

use App\Http\Controllers\Admin\AdminsController;
use App\Http\Controllers\Admin\AnalytiqueController;
use App\Http\Controllers\Admin\ChatbotAdminController;
use App\Http\Controllers\Admin\PartenaireController as AdminPartenaireController;
use App\Http\Controllers\Admin\AtelierController;
use App\Http\Controllers\Admin\CodePromoController as AdminCodePromoController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DiagnosticController;
use App\Http\Controllers\Admin\FideliteController;
use App\Http\Controllers\Admin\ListeNoireController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\NiveauConfigController;
use App\Http\Controllers\Admin\OffreSpecialeController;
use App\Http\Controllers\Admin\PaiementController;
use App\Http\Controllers\Admin\AtelierVideoController as AdminAtelierVideoController;
use App\Http\Controllers\Admin\RealisationController as AdminRealisationController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Api\SignalementController;
use App\Http\Controllers\Api\VitrineController;
use Illuminate\Support\Facades\Route;

// ─── Auth admin (publique) ────────────────────────────────────────────────────
Route::post('auth/login', [AuthController::class, 'login']);

// ─── Routes protégées admin ───────────────────────────────────────────────────
Route::middleware(['auth:admin', 'admin.auth'])->group(function () {

    Route::post('auth/logout',           [AuthController::class, 'logout']);
    Route::get('auth/me',               [AuthController::class, 'me']);
    Route::put('auth/change-password',  [AuthController::class, 'changePassword']);

    // P110-111 : diagnostic système (queue, jobs échoués, base, stockage, dernières erreurs).
    Route::get('diagnostic', [DiagnosticController::class, 'index']);

    // P202 Phase 5 : tableau de bord analytique (vues globale/clients/designers/tendances).
    Route::get('analytique', [AnalytiqueController::class, 'index']);

    // Brief 16/07 (pt 1) : dashboard chatbot (questions mal traitées → FAQ) + base d'intentions.
    Route::get('chatbot/analyse',  [ChatbotAdminController::class, 'analyse']);
    Route::get('chatbot/intents',  [ChatbotAdminController::class, 'intents']);
    Route::put('chatbot/intents',  [ChatbotAdminController::class, 'setIntents']);
    Route::get('chatbot/contexte', [ChatbotAdminController::class, 'contexte']);
    Route::put('chatbot/contexte', [ChatbotAdminController::class, 'setContexte']);

    // Pages légales du footer : édition via l'éditeur riche du back-office.
    Route::get('pages',        [\App\Http\Controllers\Api\PageLegaleController::class, 'index']);
    Route::get('pages/{cle}',  [\App\Http\Controllers\Api\PageLegaleController::class, 'showAdmin']);
    Route::put('pages/{cle}',  [\App\Http\Controllers\Api\PageLegaleController::class, 'update']);

    // Veille opportunités (n8n) : consultation des résultats hebdo dans l'admin.
    Route::get('veille', [\App\Http\Controllers\Api\VeilleController::class, 'index']);

    // Signalements (modération)
    Route::get('signalements',                        [SignalementController::class, 'index']);
    Route::post('signalements/{signalement}/traiter', [SignalementController::class, 'traiter']);

    // Bannière vitrine (publicité)
    Route::get('vitrine/banniere', [VitrineController::class, 'banniere']);
    Route::put('vitrine/banniere', [VitrineController::class, 'setBanniere']);

    // Offres de sponsorisation (mise en avant vitrine, config-driven)
    Route::get('vitrine/sponsorisation', [VitrineController::class, 'sponsorisation']);
    Route::put('vitrine/sponsorisation', [VitrineController::class, 'setSponsorisation']);

    // Brief 16/07 (pt 6) : périodes d'habillage saisonnier (splash local, config-driven)
    Route::put('vitrine/splash-themes', [VitrineController::class, 'setSplashThemes']);

    // Point 57 : catalogue d'événements dynamiques (célébrations), config-driven.
    Route::get('vitrine/evenements', [VitrineController::class, 'getEvenements']);
    Route::put('vitrine/evenements', [VitrineController::class, 'setEvenements']);

    // VID-5 : modération des vidéos de présentation (validation sous 24 h)
    Route::middleware('admin.permission:realisations.moderate')->group(function () {
        Route::get('atelier-videos/compteurs',                [AdminAtelierVideoController::class, 'compteurs']);
        Route::get('atelier-videos',                          [AdminAtelierVideoController::class, 'index']);
        Route::post('atelier-videos/{atelier_video}/approuver', [AdminAtelierVideoController::class, 'approuver']);
        Route::post('atelier-videos/{atelier_video}/refuser',   [AdminAtelierVideoController::class, 'refuser']);
    });

    // Point 101 : modération des réalisations (file d'attente, approbation+filigrane, refus)
    Route::middleware('admin.permission:realisations.moderate')->group(function () {
        Route::get('realisations/compteurs',              [AdminRealisationController::class, 'compteurs']);
        Route::get('realisations',                        [AdminRealisationController::class, 'index']);
        Route::post('realisations/{realisation}/approuver', [AdminRealisationController::class, 'approuver']);
        Route::post('realisations/{realisation}/retoucher', [AdminRealisationController::class, 'retoucher']);
        Route::post('realisations/{realisation}/refuser',   [AdminRealisationController::class, 'refuser']);
    });

    // Ateliers
    Route::middleware('admin.permission:ateliers.view')->group(function () {
        Route::get('ateliers',          [AtelierController::class, 'index']);
        Route::get('ateliers/{atelier}',[AtelierController::class, 'show']);
    });
    Route::middleware('admin.permission:ateliers.freeze')->group(function () {
        Route::post('ateliers/{atelier}/geler',   [AtelierController::class, 'geler']);
        Route::post('ateliers/{atelier}/degeler', [AtelierController::class, 'degeler']);
        Route::post('ateliers/{atelier}/verifier',   [AtelierController::class, 'verifier']);
        Route::post('ateliers/{atelier}/sponsoriser', [AtelierController::class, 'sponsoriser']);
        Route::post('ateliers/{atelier}/type',        [AtelierController::class, 'changerType']);
    });
    Route::middleware('admin.permission:ateliers.view')->group(function () {
        Route::post('ateliers/{atelier}/demo',         [AtelierController::class, 'demo']);
        Route::post('ateliers/{atelier}/trial',        [AtelierController::class, 'trial']);
        Route::get('ateliers/{atelier}/sous-ateliers', [AtelierController::class, 'sousAteliers']);
        Route::post('ateliers/{atelier}/trial-global', [AtelierController::class, 'trialGlobal']);
    });

    // Plans
    Route::middleware('admin.permission:plans.view')->group(function () {
        Route::get('plans',        [NiveauConfigController::class, 'index']);
        Route::get('plans/{plan}', [NiveauConfigController::class, 'show']);
    });
    Route::middleware('admin.permission:plans.create')->post('plans', [NiveauConfigController::class, 'store']);
    Route::middleware('admin.permission:plans.edit')->group(function () {
        Route::put('plans/{plan}',          [NiveauConfigController::class, 'update']);
        Route::post('plans/{plan}/toggle',  [NiveauConfigController::class, 'toggle']);
    });

    // Transactions (codes d'activation manuels)
    Route::middleware('admin.permission:transactions.view')->get('transactions', [TransactionController::class, 'index']);
    Route::middleware('admin.permission:transactions.create')->post('transactions', [TransactionController::class, 'store']);
    Route::middleware('admin.permission:transactions.cancel')->delete('transactions/{transaction}', [TransactionController::class, 'cancel']);

    // Paiements
    Route::middleware('admin.permission:paiements.view')->get('paiements', [PaiementController::class, 'index']);
    Route::middleware('admin.permission:paiements.validate')->post('paiements/{paiement}/valider', [PaiementController::class, 'valider']);
    Route::middleware('admin.permission:paiements.refund')->post('paiements/{paiement}/rembourser', [PaiementController::class, 'rembourser']);

    // Tickets support
    Route::middleware('admin.permission:tickets.view')->group(function () {
        Route::get('tickets',          [TicketController::class, 'index']);
        Route::get('tickets/{ticket}', [TicketController::class, 'show']);
    });
    Route::middleware('admin.permission:tickets.assign')->post('tickets/{ticket}/assigner', [TicketController::class, 'assigner']);
    Route::middleware('admin.permission:tickets.respond')->post('tickets/{ticket}/repondre', [TicketController::class, 'repondre']);
    Route::middleware('admin.permission:tickets.close')->group(function () {
        Route::post('tickets/{ticket}/fermer',  [TicketController::class, 'fermer']);
        Route::post('tickets/{ticket}/rouvrir', [TicketController::class, 'rouvrir']);
    });

    // Offres spéciales
    Route::middleware('admin.permission:offres.view')->get('offres', [OffreSpecialeController::class, 'index']);
    Route::middleware('admin.permission:offres.create')->group(function () {
        Route::post('offres',          [OffreSpecialeController::class, 'store']);
        Route::put('offres/{offre}',   [OffreSpecialeController::class, 'update']);
        Route::delete('offres/{offre}',[OffreSpecialeController::class, 'destroy']);
    });

    // Codes promo / ambassadeurs (P153-158)
    Route::middleware('admin.permission:promo.view')->group(function () {
        Route::get('codes-promo',              [AdminCodePromoController::class, 'index']);
        Route::get('codes-promo/{codePromo}',  [AdminCodePromoController::class, 'show']);
    });
    Route::middleware('admin.permission:promo.manage')->group(function () {
        Route::post('codes-promo',                     [AdminCodePromoController::class, 'store']);
        Route::post('codes-promo/{codePromo}/toggle',  [AdminCodePromoController::class, 'toggle']);
    });

    // Liste noire
    Route::middleware('admin.permission:blacklist.manage')->group(function () {
        Route::get('liste-noire',                      [ListeNoireController::class, 'index']);
        Route::post('liste-noire',                     [ListeNoireController::class, 'store']);
        Route::delete('liste-noire/{listeNoire}',      [ListeNoireController::class, 'destroy']);
    });

    // Audit log
    Route::middleware('admin.permission:audit.view')->get('audit', [AuditLogController::class, 'index']);

    // Fidélité
    Route::middleware('admin.permission:fidelite.view')->get('ateliers/{atelier}/fidelite', [FideliteController::class, 'show']);
    Route::middleware('admin.permission:fidelite.adjust')->post('ateliers/{atelier}/fidelite/ajuster', [FideliteController::class, 'ajuster']);

    // Notifications
    Route::middleware('admin.permission:notifications.broadcast')->post('notifications', [NotificationController::class, 'store']);

    // P204 : partenaires + candidatures
    Route::middleware('admin.permission:partenaires.manage')->group(function () {
        Route::get('partenaires',                       [AdminPartenaireController::class, 'index']);
        Route::post('partenaires',                      [AdminPartenaireController::class, 'store']);
        Route::match(['put', 'post'], 'partenaires/{partenaire}', [AdminPartenaireController::class, 'update']);
        Route::delete('partenaires/{partenaire}',       [AdminPartenaireController::class, 'destroy']);
        Route::get('candidatures-partenaires',          [AdminPartenaireController::class, 'candidatures']);
        Route::post('candidatures-partenaires/{candidature}/statut', [AdminPartenaireController::class, 'statutCandidature']);
    });

    // Gestion des admins (super_admin seulement via admins.manage)
    Route::middleware('admin.permission:admins.manage')->group(function () {
        Route::get('admins',           [AdminsController::class, 'index']);
        Route::post('admins',          [AdminsController::class, 'store']);
        Route::put('admins/{admin}',   [AdminsController::class, 'update']);
        Route::delete('admins/{admin}',[AdminsController::class, 'destroy']);
    });
});
