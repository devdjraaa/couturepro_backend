<?php

namespace App\Console\Commands;

use App\Models\VitrineSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Le pré-rendu sert-il vraiment quelque chose aux robots ?
 *
 * Défaut réel, corrigé le 23/07 : le SeoRenderController fonctionnait depuis le
 * 20/07, mais AUCUNE règle nginx ne lui envoyait de robot. Googlebot recevait la
 * coquille de l'application. Google a indexé une page vide pendant des semaines,
 * et deux fichiers de suivi se contredisaient sur le sujet — c'est un curl
 * manuel qui a tranché.
 *
 * Ce contrôle remplace ce curl manuel. Il repose sur un fait simple et
 * vérifiable : **le titre servi à un robot doit DIFFÉRER de celui servi à un
 * navigateur**. S'ils sont identiques, le routage est inerte.
 *
 * ⚠️ Volontairement SANS modèle de langage. Le contrôle est binaire — deux
 * titres sont égaux ou ils ne le sont pas. Y ajouter un modèle en cours
 * d'apprentissage n'apporterait aucune information et introduirait un mode de
 * défaillance : une réponse mal formée passerait pour un verdict. Sur la veille
 * des opportunités, le même modèle rendait 0 sélection sur 25 avec des
 * justifications inventées avant d'être bridé.
 */
class VeilleRenduRobots extends Command
{
    protected $signature = 'veille:rendu-robots
                            {--url=* : Limiter à certaines adresses}
                            {--silencieux : Ne pas envoyer d\'alerte}';

    protected $description = 'Vérifie que les robots reçoivent le pré-rendu et non la coquille de l\'application';

    /** L'agent que nous présentons — celui de Googlebot, à l'identique. */
    private const AGENT_ROBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    private const AGENT_NAVIGATEUR = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /** Trace du dernier passage : sert au chien de garde du chien de garde. */
    public const CLE_DERNIER_PASSAGE = 'veille.rendu_robots.dernier_passage';

    public function handle(): int
    {
        $adresses = $this->option('url') ?: $this->adressesSurveillees();
        $anomalies = [];
        $lignes = ['VEILLE — RENDU AUX ROBOTS · '.now()->format('d/m/Y H:i'), str_repeat('=', 52)];

        foreach ($adresses as $adresse) {
            $lignes[] = '';
            $lignes[] = "▶ {$adresse}";

            $robot = $this->recuperer($adresse, self::AGENT_ROBOT);
            $humain = $this->recuperer($adresse, self::AGENT_NAVIGATEUR);

            if ($robot['erreur'] || $humain['erreur']) {
                $souci = $robot['erreur'] ?: $humain['erreur'];
                $lignes[] = "  INJOIGNABLE : {$souci}";
                $anomalies[] = "{$adresse} : injoignable ({$souci})";

                continue;
            }

            $lignes[] = "  robot      : {$robot['statut']} — « {$this->court($robot['titre'])} »";
            $lignes[] = "  navigateur : {$humain['statut']} — « {$this->court($humain['titre'])} »";

            foreach ($this->controler($adresse, $robot, $humain) as $probleme) {
                $lignes[] = "  ⚠ {$probleme}";
                $anomalies[] = "{$adresse} : {$probleme}";
            }
        }

        $lignes[] = '';
        $lignes[] = $anomalies
            ? '⚠ ANOMALIES ('.count($anomalies).') : '.implode(' | ', $anomalies)
            : '✓ Tous les robots reçoivent bien le pré-rendu.';

        $rapport = implode("\n", $lignes);
        $this->line($rapport);

        // On note le passage AVANT d'alerter : même une exécution en anomalie
        // prouve que la surveillance tourne.
        Cache::forever(self::CLE_DERNIER_PASSAGE, now()->toIso8601String());

        if ($anomalies && ! $this->option('silencieux')) {
            $this->alerter($rapport, $anomalies);
        }

        return $anomalies ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Les adresses surveillées, éditables sans déploiement.
     *
     * Une liste figée dans le code vieillirait : une page ajoutée demain ne
     * serait jamais contrôlée, et personne ne s'en apercevrait.
     */
    private function adressesSurveillees(): array
    {
        $defaut = [
            'https://gextimo.novafriq.africa/',
            'https://gextimo.novafriq.africa/createurs',
        ];

        try {
            $liste = VitrineSetting::where('cle', 'veille_rendu_urls')->value('valeur');
        } catch (\Throwable) {
            return $defaut;
        }

        if (is_string($liste)) {
            $liste = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $liste)));
        }

        // Seules des adresses complètes sont retenues : une ligne mal saisie
        // ferait échouer le contrôle et l'anomalie porterait sur la saisie, pas
        // sur le site.
        $liste = array_filter((array) $liste, fn ($u) => is_string($u) && str_starts_with($u, 'http'));

        return $liste ? array_values($liste) : $defaut;
    }

    private function recuperer(string $adresse, string $agent): array
    {
        try {
            $r = Http::withHeaders(['User-Agent' => $agent])
                ->timeout(20)
                ->withoutRedirecting()
                ->get($adresse);

            $corps = $r->body();

            return [
                'erreur'  => null,
                'statut'  => $r->status(),
                'titre'   => $this->extraire('#<title[^>]*>(.*?)</title>#is', $corps),
                'desc'    => $this->extraire('#<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']#is', $corps),
                'octets'  => strlen($corps),
            ];
        } catch (\Throwable $e) {
            return ['erreur' => $e->getMessage(), 'statut' => null, 'titre' => '', 'desc' => '', 'octets' => 0];
        }
    }

    /**
     * Les contrôles, tous déterministes.
     *
     * @return array<int, string>
     */
    private function controler(string $adresse, array $robot, array $humain): array
    {
        $problemes = [];

        if ($robot['statut'] !== 200) {
            $problemes[] = "le robot reçoit un code {$robot['statut']}";
        }

        if (blank($robot['titre'])) {
            $problemes[] = 'la réponse au robot n\'a aucun titre';
        }

        // LE contrôle central. Sur une page autre que l'accueil, deux titres
        // identiques signifient que le robot a reçu la coquille de
        // l'application : le routage est inerte, exactement comme avant le
        // 23/07.
        $chemin = parse_url($adresse, PHP_URL_PATH) ?: '/';
        if ($chemin !== '/' && $robot['titre'] !== '' && $robot['titre'] === $humain['titre']) {
            $problemes[] = 'le robot reçoit le MÊME titre qu\'un navigateur — le pré-rendu ne s\'applique plus';
        }

        if (blank($robot['desc'])) {
            $problemes[] = 'la réponse au robot n\'a aucune description';
        }

        // Une réponse minuscule trahit une coquille vide ; une réponse énorme
        // trahit l'application complète servie au robot au lieu du pré-rendu.
        if ($robot['octets'] > 0 && $robot['octets'] < 300) {
            $problemes[] = "la réponse au robot est vide ou presque ({$robot['octets']} octets)";
        }

        return $problemes;
    }

    private function alerter(string $rapport, array $anomalies): void
    {
        $destinataire = config('mail.veille_seo') ?: env('VEILLE_SEO_EMAIL');

        Log::warning('Veille rendu robots : anomalie', ['anomalies' => $anomalies]);

        if (! $destinataire) {
            $this->warn('Aucun destinataire configuré (VEILLE_SEO_EMAIL) — alerte journalisée seulement.');

            return;
        }

        try {
            Mail::raw($rapport, function ($m) use ($destinataire, $anomalies) {
                $m->to($destinataire)->subject(
                    '⚠ Rendu aux robots — '.count($anomalies).' anomalie(s) · '.now()->format('d/m/Y H:i')
                );
            });
        } catch (\Throwable $e) {
            // Une messagerie indisponible ne doit pas faire échouer la veille :
            // le journal garde la trace, c'est l'essentiel.
            Log::error('Alerte rendu robots non envoyée', ['erreur' => $e->getMessage()]);
        }
    }

    private function extraire(string $motif, string $corps): string
    {
        return preg_match($motif, $corps, $m) ? trim(html_entity_decode($m[1])) : '';
    }

    private function court(string $t): string
    {
        return mb_strlen($t) > 58 ? mb_substr($t, 0, 55).'…' : ($t ?: '(vide)');
    }
}
