<?php

use App\Http\Controllers\Api\Auth\EquipeMembreAuthController;
use App\Http\Controllers\Api\Auth\ProprietaireAuthController;
use App\Http\Controllers\Api\Auth\RecuperationController;
use App\Http\Controllers\Api\AbonnementController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\CommandePaiementController;
use App\Http\Controllers\Api\EquipeMembreController;
use App\Http\Controllers\Api\FideliteController;
use App\Http\Controllers\Api\MesureController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ParametresController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TicketSupportController;
use App\Http\Controllers\Api\VetementController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\WhatsAppController;
use Illuminate\Support\Facades\Route;

// ─── Route d'entrée ─────────────────────────────────────────────────────────
Route::get('/', fn() => response()->json([
    'app'     => config('app.name'),
    'version' => '1.0.0',
    'status'  => 'ok',
]));

// ─── Auth publique ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('inscription',   [ProprietaireAuthController::class, 'inscription']);
    Route::post('verifier-otp',  [ProprietaireAuthController::class, 'verifierOtp']);
    Route::post('renvoyer-otp',  [ProprietaireAuthController::class, 'renvoyerOtp']);
    Route::post('login',         [ProprietaireAuthController::class, 'login']);
    Route::post('equipe/login',  [EquipeMembreAuthController::class, 'login']);

    Route::prefix('recuperation')->group(function () {
        Route::post('initier',              [RecuperationController::class, 'etape1']);
        Route::post('verifier-otp',         [RecuperationController::class, 'etape2']);
        Route::post('nouveau-telephone',    [RecuperationController::class, 'etape3']);
        Route::post('verifier-otp-nouveau', [RecuperationController::class, 'etape4']);
        Route::post('nouveau-mot-de-passe', [RecuperationController::class, 'etape5']);
    });
});

// ─── Routes protégées Sanctum ────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('auth/logout', [ProprietaireAuthController::class, 'logout']);
    Route::get('auth/me',      [ProprietaireAuthController::class, 'me']);

    // Clients
    Route::get('clients',                    [ClientController::class, 'index']);
    Route::post('clients',                   [ClientController::class, 'store']);
    Route::get('clients/{client}',           [ClientController::class, 'show']);
    Route::put('clients/{client}',           [ClientController::class, 'update']);
    Route::delete('clients/{client}',        [ClientController::class, 'destroy']);
    Route::post('clients/{client}/archiver', [ClientController::class, 'archiver']);

    // Mesures
    Route::get('clients/{clientId}/mesures', [MesureController::class, 'index']);
    Route::post('mesures',                   [MesureController::class, 'store']);
    Route::put('mesures/{mesure}',           [MesureController::class, 'update']);
    Route::delete('mesures/{mesure}',        [MesureController::class, 'destroy']);

    // Commandes
    Route::get('commandes',               [CommandeController::class, 'index']);
    Route::post('commandes',              [CommandeController::class, 'store']);
    Route::get('commandes/{commande}',    [CommandeController::class, 'show']);
    Route::match(['PUT', 'POST'], 'commandes/{commande}', [CommandeController::class, 'update']);
    Route::delete('commandes/{commande}', [CommandeController::class, 'destroy']);
    // Paiements de commande
    Route::get('commandes/{commande}/paiements',  [CommandePaiementController::class, 'index']);
    Route::post('commandes/{commande}/paiements', [CommandePaiementController::class, 'store']);

    // Vêtements
    Route::get('vetements',               [VetementController::class, 'index']);
    Route::post('vetements',              [VetementController::class, 'store']);
    Route::match(['PUT', 'POST'], 'vetements/{vetement}', [VetementController::class, 'update']);
    Route::delete('vetements/{vetement}', [VetementController::class, 'destroy']);

    // Équipe
    Route::get('equipe',             [EquipeMembreController::class, 'index']);
    Route::post('equipe',            [EquipeMembreController::class, 'store']);
    Route::delete('equipe/{membre}', [EquipeMembreController::class, 'destroy']);

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
    Route::get('abonnement/plans',   [AbonnementController::class, 'plans']);
    Route::get('abonnement/current', [AbonnementController::class, 'current']);

    // Notifications
    Route::get('notifications',              [NotificationController::class, 'index']);
    Route::post('notifications/mark-as-read',[NotificationController::class, 'markAsRead']);

    // Tickets support (propriétaire)
    Route::get('support/tickets',              [TicketSupportController::class, 'index']);
    Route::post('support/tickets',             [TicketSupportController::class, 'store']);
    Route::get('support/tickets/{id}',         [TicketSupportController::class, 'show']);
    Route::post('support/tickets/{id}/repondre', [TicketSupportController::class, 'repondre']);

    // Paramètres
    Route::put('parametres/profil',          [ParametresController::class, 'updateProfil']);
    Route::put('parametres/atelier',         [ParametresController::class, 'updateAtelier']);
    Route::get('parametres/communications',  [ParametresController::class, 'getCommunications']);
    Route::put('parametres/communications',  [ParametresController::class, 'updateCommunications']);
    Route::put('parametres/mot-de-passe',    [ParametresController::class, 'changerMotDePasse']);

    // WhatsApp
    Route::get('whatsapp/rappel-client/{clientId}', [WhatsAppController::class, 'rappelClient']);
});

// ─── Webhooks (pas d'auth) ───────────────────────────────────────────────────
Route::post('webhooks/{provider}', [WebhookController::class, 'handle']);
