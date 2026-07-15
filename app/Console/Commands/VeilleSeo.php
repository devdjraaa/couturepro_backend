<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

// P200 : veille technique SEO hebdomadaire — 2 sites séparés.
//  - PageSpeed Insights (mobile + desktop) via l'API publique PSI
//  - disponibilité HTTP + validité/expiration du certificat HTTPS
//  - rapport dans storage/app/veille/ + e-mail si VEILLE_SEO_EMAIL est configuré
// Hors périmètre (voulu) : contenu, backlinks, réseaux sociaux.
class VeilleSeo extends Command
{
    protected $signature = 'veille:seo {--site=* : Limiter à certains sites}';

    protected $description = 'Veille technique SEO hebdo (PageSpeed, HTTPS, disponibilité) — P200';

    private const SITES = [
        'https://novafriq.africa',
        'https://gextimo.novafriq.africa',
    ];

    public function handle(): int
    {
        $sites = $this->option('site') ?: self::SITES;
        $lignes = ['VEILLE SEO — '.now()->format('d/m/Y H:i'), str_repeat('=', 48)];
        $alertes = [];

        foreach ($sites as $site) {
            $host = parse_url($site, PHP_URL_HOST);
            $lignes[] = '';
            $lignes[] = "▶ {$site}";

            // 1. Disponibilité HTTP
            try {
                $resp = Http::timeout(20)->get($site);
                $status = $resp->status();
                $lignes[] = "  HTTP : {$status}";
                if (! $resp->successful()) {
                    $alertes[] = "{$host} : HTTP {$status}";
                }
            } catch (\Throwable $e) {
                $lignes[] = '  HTTP : INJOIGNABLE ('.$e->getMessage().')';
                $alertes[] = "{$host} : injoignable";
            }

            // 2. Certificat HTTPS (jours restants)
            $joursCert = $this->joursCertificat($host);
            if ($joursCert !== null) {
                $lignes[] = "  HTTPS : certificat expire dans {$joursCert} j";
                if ($joursCert < 14) {
                    $alertes[] = "{$host} : certificat HTTPS expire dans {$joursCert} j";
                }
            } else {
                $lignes[] = '  HTTPS : vérification du certificat impossible';
                $alertes[] = "{$host} : certificat HTTPS non vérifiable";
            }

            // 3. PageSpeed Insights (performance) mobile + desktop
            foreach (['mobile', 'desktop'] as $strategy) {
                $score = $this->scorePsi($site, $strategy);
                if ($score !== null) {
                    $lignes[] = "  PSI {$strategy} : {$score}/100";
                    if ($score < 50) {
                        $alertes[] = "{$host} : PageSpeed {$strategy} faible ({$score}/100)";
                    }
                } else {
                    $lignes[] = "  PSI {$strategy} : indisponible";
                }
            }
        }

        $lignes[] = '';
        $lignes[] = $alertes
            ? '⚠ ALERTES ('.count($alertes).') : '.implode(' | ', $alertes)
            : '✓ Aucune alerte.';

        $rapport = implode("\n", $lignes);
        Storage::put('veille/'.now()->format('Y-m-d').'.txt', $rapport);
        $this->line($rapport);

        // E-mail (uniquement si une adresse est configurée — VEILLE_SEO_EMAIL)
        $dest = config('services.veille_seo.email');
        if ($dest) {
            try {
                Mail::raw($rapport, function ($m) use ($dest, $alertes) {
                    $m->to($dest)->subject(
                        ($alertes ? '⚠ ' : '✓ ').'Veille SEO NovAfriq — '.now()->format('d/m/Y')
                    );
                });
            } catch (\Throwable $e) {
                Log::warning('Veille SEO : envoi e-mail échoué — '.$e->getMessage());
            }
        }

        return self::SUCCESS;
    }

    private function joursCertificat(string $host): ?int
    {
        try {
            $ctx = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => true]]);
            $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
            if (! $client) {
                return null;
            }
            $params = stream_context_get_params($client);
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            fclose($client);

            return $cert ? (int) floor(($cert['validTo_time_t'] - time()) / 86400) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function scorePsi(string $site, string $strategy): ?int
    {
        try {
            // Sans clé, le quota PSI anonyme (partagé) tombe vite en 429 →
            // configurer PSI_API_KEY (clé gratuite Google Cloud, 25k req/jour).
            $params = [
                'url'      => $site,
                'strategy' => $strategy,
                'category' => 'performance',
            ];
            if ($key = config('services.veille_seo.psi_key')) {
                $params['key'] = $key;
            }
            $resp = Http::timeout(90)->get('https://www.googleapis.com/pagespeedonline/v5/runPagespeed', $params);
            if (! $resp->successful()) {
                return null;
            }
            $score = $resp->json('lighthouseResult.categories.performance.score');

            return $score !== null ? (int) round($score * 100) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
