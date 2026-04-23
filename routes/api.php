<?php

use App\Http\Controllers\Api\Auth\EquipeMembreAuthController;
use App\Http\Controllers\Api\Auth\ProprietaireAuthController;
use App\Http\Controllers\Api\Auth\RecuperationController;
use Illuminate\Support\Facades\Route;

// Auth proprietaire
Route::prefix('auth')->group(function () {
    Route::post('inscription',   [ProprietaireAuthController::class, 'inscription']);
    Route::post('verifier-otp',  [ProprietaireAuthController::class, 'verifierOtp']);
    Route::post('login',         [ProprietaireAuthController::class, 'login']);

    // Récupération de compte (5 étapes)
    Route::prefix('recuperation')->group(function () {
        Route::post('initier',              [RecuperationController::class, 'etape1']);
        Route::post('verifier-otp',         [RecuperationController::class, 'etape2']);
        Route::post('nouveau-telephone',    [RecuperationController::class, 'etape3']);
        Route::post('verifier-otp-nouveau', [RecuperationController::class, 'etape4']);
        Route::post('nouveau-mot-de-passe', [RecuperationController::class, 'etape5']);
    });

    // Auth équipe membre
    Route::post('equipe/login', [EquipeMembreAuthController::class, 'login']);

    // Routes protégées
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [ProprietaireAuthController::class, 'logout']);
        Route::get('me',      [ProprietaireAuthController::class, 'me']);
    });
});
