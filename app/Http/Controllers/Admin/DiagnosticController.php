<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Atelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// P110-111 : outil de diagnostic admin — santé système en un coup d'œil pour le support
// (queue, jobs échoués, base, stockage, dernières erreurs de log, modules actifs).
// Chaque bloc est isolé en try/catch : un check indisponible ne casse pas le diagnostic.
class DiagnosticController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'genere_a'   => now()->toIso8601String(),
            'app'        => $this->app(),
            'file'       => $this->queue(),
            'base'       => $this->database(),
            'stockage'   => $this->stockage(),
            'modules'    => $this->modules(),
            'ota'        => $this->ota(),
            'erreurs'    => $this->dernieresErreurs(),
        ]);
    }

    private function safe(callable $fn, $defaut = null)
    {
        try { return $fn(); } catch (\Throwable $e) { return $defaut; }
    }

    private function app(): array
    {
        return [
            'env'         => app()->environment(),
            'debug'       => (bool) config('app.debug'),
            'version_app' => env('APP_LATEST_VERSION'),
            'php'         => PHP_VERSION,
            'laravel'     => app()->version(),
            'cache_config' => file_exists(base_path('bootstrap/cache/config.php')),
            'cache_routes' => file_exists(base_path('bootstrap/cache/routes-v7.php')),
        ];
    }

    private function queue(): array
    {
        return [
            'connexion'      => config('queue.default'),
            'en_attente'     => $this->safe(fn () => DB::table('jobs')->count(), null),
            'echecs'         => $this->safe(fn () => DB::table('failed_jobs')->count(), null),
            'dernier_echec'  => $this->safe(fn () => optional(DB::table('failed_jobs')->orderByDesc('failed_at')->first())->failed_at, null),
        ];
    }

    private function database(): array
    {
        $ok      = $this->safe(fn () => DB::select('select 1') ? true : false, false);
        $driver  = config('database.default');
        $taille  = $this->safe(function () use ($driver) {
            if ($driver === 'pgsql') {
                return DB::selectOne('select pg_size_pretty(pg_database_size(current_database())) as t')->t;
            }
            return null;
        }, null);

        return [
            'connexion'          => $ok,
            'driver'             => $driver,
            'taille'             => $taille,
            'migrations_ran'     => $this->safe(fn () => DB::table('migrations')->count(), null),
        ];
    }

    private function stockage(): array
    {
        $total = $this->safe(fn () => disk_total_space(base_path()), null);
        $libre = $this->safe(fn () => disk_free_space(base_path()), null);
        $usePct = ($total && $libre !== null) ? round(($total - $libre) / $total * 100, 1) : null;

        return [
            'total_go'   => $total !== null ? round($total / 1073741824, 1) : null,
            'libre_go'   => $libre !== null ? round($libre / 1073741824, 1) : null,
            'utilise_pct' => $usePct,
        ];
    }

    private function modules(): array
    {
        return [
            'ateliers_total'   => $this->safe(fn () => Atelier::count(), null),
            'ateliers_actifs'  => $this->safe(fn () => Atelier::where('statut', 'actif')->count(), null),
            'ateliers_geles'   => $this->safe(fn () => Atelier::where('statut', 'gele')->count(), null),
            'abonnements_actifs' => $this->safe(fn () => Abonnement::where('statut', 'actif')->count(), null),
            'derniere_veille_seo' => $this->safe(fn () => Cache::get('veille_seo_last_run'), null),
        ];
    }

    /**
     * Mises à jour OTA sur les 7 derniers jours, par version — c'était invisible
     * jusqu'ici : le 22/07, la 1.0.143 a échoué en silence sur un appareil de
     * test, et rien ne l'aurait signalé sans un test manuel. Chaque appareil
     * rapporte désormais l'issue d'une tentative (voir `OtaEvenementController`) ;
     * ce bloc en fait la somme, la version la plus récente en tête.
     */
    private function ota(): array
    {
        return $this->safe(function () {
            $lignes = DB::table('gxt_ota_evenements')
                ->where('created_at', '>=', now()->subDays(7))
                ->select('app_id', 'version', 'evenement', DB::raw('count(*) as n'))
                ->groupBy('app_id', 'version', 'evenement')
                ->orderByDesc('version')
                ->get();

            $parVersion = [];
            foreach ($lignes as $l) {
                $cle = "{$l->app_id}@{$l->version}";
                $parVersion[$cle]['app_id'] ??= $l->app_id;
                $parVersion[$cle]['version'] ??= $l->version;
                $parVersion[$cle][$l->evenement] = (int) $l->n;
            }

            return array_values($parVersion);
        }, []);
    }

    /** Dernières lignes ERROR/CRITICAL du log Laravel (P110 : erreurs importantes visibles au support). */
    private function dernieresErreurs(int $max = 25): array
    {
        return $this->safe(function () use ($max) {
            $path = storage_path('logs/laravel.log');
            if (! is_file($path)) {
                return [];
            }
            // On ne lit que la fin du fichier (jusqu'à 256 Ko) pour rester léger.
            $taille = filesize($path);
            $offset = max(0, $taille - 256 * 1024);
            $fh = fopen($path, 'rb');
            fseek($fh, $offset);
            $contenu = fread($fh, 256 * 1024) ?: '';
            fclose($fh);

            $lignes = preg_split('/\R/', $contenu) ?: [];
            $erreurs = array_values(array_filter($lignes, fn ($l) => preg_match('/\.(ERROR|CRITICAL|ALERT|EMERGENCY):/', $l)));

            // Les plus récentes en premier, tronquées pour l'affichage.
            return array_map(
                fn ($l) => mb_strimwidth($l, 0, 300, '…'),
                array_slice(array_reverse($erreurs), 0, $max)
            );
        }, []);
    }
}
