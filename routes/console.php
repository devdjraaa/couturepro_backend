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

// ─── Veille SEO (P200) ──────────────────────────────────────────────────────
// Rapport technique hebdo (PageSpeed, HTTPS, dispo) — lundi 7h, 2 sites.
Schedule::command('veille:seo')->weeklyOn(1, '07:00');

// Le pre-rendu aux robots se verifie TOUTES LES 6 H, pas une fois par semaine :
// il peut redevenir inerte au prochain deploiement, et chaque jour de silence
// est un jour ou Google indexe une page vide. C'est exactement ce qui s'est
// produit entre le 20 et le 23/07.
//   `withoutOverlapping` : une execution lente ne doit pas en croiser une autre.
//   `runInBackground`    : ne pas retarder les autres taches planifiees.
Schedule::command('veille:rendu-robots')
    ->everySixHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/veille.log'));

// Veille opportunités : QUOTIDIENNE (elle était hebdomadaire et ratait
// l'essentiel). De nuit, car le tri par Makila tourne sur le modèle local et
// prend une trentaine de secondes par article.
// Le cron du serveur envoie toute la sortie dans /dev/null : une tâche qui
// échoue le fait donc en silence, et le seul indice serait un digest vide le
// lendemain matin, sans cause visible. On garde une trace, et on ne relance
// pas une collecte par-dessus une autre si la précédente traîne.
Schedule::command('veille:opportunites')
    ->dailyAt('04:30')
    ->withoutOverlapping(30)
    ->appendOutputTo(storage_path('logs/veille.log'));

// Une heure et demie après l'envoi prévu du digest : le temps que l'automate
// ait eu sa chance, sans attendre la journée pour constater un silence.
Schedule::command('veille:verifier-digest')
    ->dailyAt('09:00')
    ->appendOutputTo(storage_path('logs/veille.log'));

// ─── Espace client (P202 Phase 4) ───────────────────────────────────────────
// Recalcul nocturne des synthèses client (engagement/segment/RFM/CLV) et designer
// (score de confiance, revenus/prédiction) à partir de gxt_evenements + commandes.
Schedule::command('gxt:recalculer-metrics')->dailyAt('02:30');

// Brief 16/07 (pts 3+6) : vœux d'anniversaire aux clients vitrine (message discret, Brevo).
Schedule::command('gxt:anniversaires')->dailyAt('08:00');

// Journal OTA (voir OtaEvenementController) : purge hebdomadaire, un journal
// n'a pas besoin de grossir indéfiniment pour rester utile au diagnostic.
Schedule::command('ota:purger-evenements')->weeklyOn(1, '03:00');
