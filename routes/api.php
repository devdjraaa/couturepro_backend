<?php

use App\Http\Controllers\Api\Auth\EquipeMembreAuthController;
use App\Http\Controllers\Api\Auth\ProprietaireAuthController;
use App\Http\Controllers\Api\Auth\RecuperationController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\MesureController;
use App\Http\Controllers\Api\PaiementController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\VetementController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// ─── Auth publique ───────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('inscription',  [ProprietaireAuthController::class, 'inscription']);
    Route::post('verifier-otp', [ProprietaireAuthController::class, 'verifierOtp']);
    Route::post('login',        [ProprietaireAuthController::class, 'login']);
    Route::post('equipe/login', [EquipeMembreAuthController::class, 'login']);

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
    Route::get('clients',                  [ClientController::class, 'index']);
    Route::post('clients',                 [ClientController::class, 'store']);
    Route::get('clients/{client}',         [ClientController::class, 'show']);
    Route::put('clients/{client}',         [ClientController::class, 'update']);
    Route::delete('clients/{client}',      [ClientController::class, 'destroy']);
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
    Route::put('commandes/{commande}',    [CommandeController::class, 'update']);
    Route::delete('commandes/{commande}', [CommandeController::class, 'destroy']);

    // Vêtements
    Route::get('vetements',              [VetementController::class, 'index']);
    Route::post('vetements',             [VetementController::class, 'store']);
    Route::put('vetements/{vetement}',   [VetementController::class, 'update']);
    Route::delete('vetements/{vetement}',[VetementController::class, 'destroy']);

    // Sync
    Route::post('sync/push', [SyncController::class, 'push']);
    Route::get('sync/pull',  [SyncController::class, 'pull']);

    // Paiements
    Route::post('paiements/initier',        [PaiementController::class, 'initier']);
    Route::get('paiements/{paiement}/status', [PaiementController::class, 'status']);
});

// ─── Webhooks (pas d'auth) ───────────────────────────────────────────────────
Route::post('webhooks/{provider}', [WebhookController::class, 'handle']);
