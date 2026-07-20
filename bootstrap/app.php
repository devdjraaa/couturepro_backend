<?php

use App\Console\Commands\AppliquerEcheancesAbonnements;
use App\Console\Commands\BackupAteliersCloud;
use App\Console\Commands\CheckPendingPayments;
use App\Console\Commands\ExpireStalePayments;
use App\Console\Commands\NotifyAbonnementExpiry;
use App\Console\Commands\ProcessBonusExpiry;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__.'/../routes/web.php',
        api:      __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health:   '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api/admin')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // statefulApi() retiré : l'app (web + mobile Capacitor) utilise
        // exclusivement des Bearer tokens (cf. frontend src/services/api.js).
        // Avec statefulApi(), Sanctum exigeait un CSRF token, ce qui cassait
        // l'inscription depuis le WebView Android (Origin: https://localhost
        // matche le domaine stateful par défaut).
        $middleware->alias([
            'admin.auth'       => \App\Http\Middleware\AdminAuth::class,
            'admin.permission' => \App\Http\Middleware\CheckAdminPermission::class,
            // Permissions d'équipe : le référentiel n'était appliqué que côté front
            // (masquage des boutons), jamais côté serveur.
            'equipe.permission' => \App\Http\Middleware\CheckEquipePermission::class,
            'recaptcha'        => \App\Http\Middleware\VerifyRecaptcha::class,
            'account'          => \App\Http\Middleware\EnsureAccountType::class, // P202 : isole client vitrine / pro
        ]);

        // API pure : jamais de redirection invité. Sans ça, le middleware Authenticate
        // appelle route('login') (inexistante) AVANT même le handler d'exception →
        // 500 « Route [login] not defined » au lieu d'un 401 (vu en prod le 15/07).
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(ExpireStalePayments::class)->hourly();
        $schedule->command(CheckPendingPayments::class)->everyFifteenMinutes();
        $schedule->command(ProcessBonusExpiry::class)->hourly();
        $schedule->command(NotifyAbonnementExpiry::class)->dailyAt('08:00');
        // PL-10 : sauvegarde cloud par atelier (la commande applique la cadence par plan).
        $schedule->command(BackupAteliersCloud::class)->dailyAt('02:30');
        // P53-55 : downgrades programmés / expirations arrivés à échéance.
        $schedule->command(AppliquerEcheancesAbonnements::class)->hourly();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Pour les requêtes API, retourner un 401 JSON au lieu de tenter
        // une redirection vers la route web `login` (qui n'existe pas).
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Unauthenticated.',
                ], 401);
            }
        });
    })->create();
