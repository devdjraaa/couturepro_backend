<?php

use App\Http\Controllers\Admin\AdminsController;
use App\Http\Controllers\Admin\AtelierController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\FideliteController;
use App\Http\Controllers\Admin\ListeNoireController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\NiveauConfigController;
use App\Http\Controllers\Admin\OffreSpecialeController;
use App\Http\Controllers\Admin\PaiementController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\TransactionController;
use Illuminate\Support\Facades\Route;

// ─── Auth admin (publique) ────────────────────────────────────────────────────
Route::post('auth/login', [AuthController::class, 'login']);

// ─── Routes protégées admin ───────────────────────────────────────────────────
Route::middleware(['auth:admin', 'admin.auth'])->group(function () {

    Route::post('auth/logout',           [AuthController::class, 'logout']);
    Route::get('auth/me',               [AuthController::class, 'me']);
    Route::put('auth/change-password',  [AuthController::class, 'changePassword']);

    // Ateliers
    Route::middleware('admin.permission:ateliers.view')->group(function () {
        Route::get('ateliers',          [AtelierController::class, 'index']);
        Route::get('ateliers/{atelier}',[AtelierController::class, 'show']);
    });
    Route::middleware('admin.permission:ateliers.freeze')->group(function () {
        Route::post('ateliers/{atelier}/geler',   [AtelierController::class, 'geler']);
        Route::post('ateliers/{atelier}/degeler', [AtelierController::class, 'degeler']);
    });
    Route::middleware('admin.permission:ateliers.view')->group(function () {
        Route::post('ateliers/{atelier}/demo',  [AtelierController::class, 'demo']);
        Route::post('ateliers/{atelier}/trial', [AtelierController::class, 'trial']);
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

    // Gestion des admins (super_admin seulement via admins.manage)
    Route::middleware('admin.permission:admins.manage')->group(function () {
        Route::get('admins',           [AdminsController::class, 'index']);
        Route::post('admins',          [AdminsController::class, 'store']);
        Route::put('admins/{admin}',   [AdminsController::class, 'update']);
        Route::delete('admins/{admin}',[AdminsController::class, 'destroy']);
    });
});
