<?php

use App\Http\Controllers\Api\Auth\EquipeMembreAuthController;
use App\Http\Controllers\Api\Auth\ProprietaireAuthController;
use App\Http\Controllers\Api\Auth\RecuperationController;
use App\Http\Controllers\Api\AbonnementController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\AtelierProprietaireController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\CommandePaiementController;
use App\Http\Controllers\Api\EquipeMembreController;
use App\Http\Controllers\Api\FideliteController;
use App\Http\Controllers\Api\MesureController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\ParametresController;
use App\Http\Controllers\Api\PermissionsEquipeController;
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

    // Multi-ateliers (propriétaire seulement — EquipeMembre bloqué côté controller)
    Route::get('ateliers/mes-ateliers',           [AtelierProprietaireController::class, 'mesAteliers']);
    Route::post('ateliers',                       [AtelierProprietaireController::class, 'store']);
    Route::get('ateliers/{atelierIdParam}/stats', [AtelierProprietaireController::class, 'stats']);

    // Clients
    Route::get('clients',                    [ClientController::class, 'index']);
    Route::post('clients',                   [ClientController::class, 'store']);
    Route::get('clients/{client}',           [ClientController::class, 'show']);
    Route::put('clients/{client}',           [ClientController::class, 'update']);
    Route::delete('clients/{client}',        [ClientController::class, 'destroy']);
    Route::post('clients/{client}/archiver',     [ClientController::class, 'archiver']);
    Route::post('clients/{client}/desarchiver',  [ClientController::class, 'desarchiver']);
    Route::post('clients/{client}/toggle-vip',   [ClientController::class, 'toggleVip']);

    // Mesures
    Route::get('clients/{clientId}/mesures',      [MesureController::class, 'index']);
    Route::post('mesures',                        [MesureController::class, 'store']);
    Route::put('mesures/{mesure}',                [MesureController::class, 'update']);
    Route::post('mesures/{mesure}/archiver',      [MesureController::class, 'archiver']);
    Route::post('mesures/{mesure}/desarchiver',   [MesureController::class, 'desarchiver']);
    Route::delete('mesures/{mesure}',             [MesureController::class, 'destroy']);

    // Commandes
    Route::get('commandes',                           [CommandeController::class, 'index']);
    Route::post('commandes',                          [CommandeController::class, 'store']);
    Route::get('commandes/{commande}',                [CommandeController::class, 'show']);
    Route::match(['PUT', 'POST'], 'commandes/{commande}', [CommandeController::class, 'update']);
    Route::post('commandes/{commande}/archiver',      [CommandeController::class, 'archiver']);
    Route::post('commandes/{commande}/desarchiver',   [CommandeController::class, 'desarchiver']);
    Route::delete('commandes/{commande}',             [CommandeController::class, 'destroy']);

    // Archives (liste pour le patron)
    Route::get('archives', [ArchiveController::class, 'index']);
    // Paiements de commande
    Route::get('commandes/{commande}/paiements',  [CommandePaiementController::class, 'index']);
    Route::post('commandes/{commande}/paiements', [CommandePaiementController::class, 'store']);

    // Vêtements
    Route::get('vetements',               [VetementController::class, 'index']);
    Route::post('vetements',              [VetementController::class, 'store']);
    Route::match(['PUT', 'POST'], 'vetements/{vetement}', [VetementController::class, 'update']);
    Route::delete('vetements/{vetement}', [VetementController::class, 'destroy']);

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
    Route::get('parametres/preferences',     [ParametresController::class, 'getPreferences']);
    Route::put('parametres/preferences',     [ParametresController::class, 'updatePreferences']);

    // WhatsApp
    Route::get('whatsapp/rappel-client/{clientId}', [WhatsAppController::class, 'rappelClient']);
    Route::get('whatsapp/confirmation-commande/{commandeId}', [WhatsAppController::class, 'confirmationCommande']);
    Route::get('whatsapp/commande-prete/{commandeId}', [WhatsAppController::class, 'commandePrete']);
});

// ─── Webhooks (pas d'auth) ───────────────────────────────────────────────────
Route::post('webhooks/{provider}', [WebhookController::class, 'handle']);
