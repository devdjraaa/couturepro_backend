<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Paiements ──────────────────────────────────────────────────────────────
// Annule les pending > 1h, supprime les cancelled/failed/expired > 2h
Schedule::command('payments:purge-stale')->everyThirtyMinutes();

// Expire les paiements selon leur expires_at explicite
Schedule::command('payments:expire-stale')->everyFifteenMinutes();

// ─── Abonnements ────────────────────────────────────────────────────────────
// Notifie les ateliers dont l'abonnement expire bientôt (quotidien à 8h)
Schedule::command('abonnements:notify-expiry')->dailyAt('08:00');

// Expire les bonus de fidélité arrivés à terme (quotidien à 1h du matin)
Schedule::command('abonnements:process-bonus-expiry')->dailyAt('01:00');
