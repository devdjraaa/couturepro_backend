<?php

namespace App\Console\Commands;

use App\Models\VitrineSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Veille opportunités — collecte QUOTIDIENNE, centrée sur le Bénin.
 *
 * Elle passait par un automate n8n interrogeant des requêtes génériques en
 * anglais : elle remontait un marché de Noël à Laudun (France) et des articles
 * MSN, et ratait l'essentiel — la première Nuit du Kanvo Indigo au Bénin n'y a
 * jamais figuré. Elle ne tournait qu'une fois par semaine, et l'automate
 * n'écrivait plus rien depuis six jours.
 *
 * Reprise ici parce que la planification Laravel est déjà en place et fiable :
 * une brique de moins à surveiller, des sources éditables en base, et un
 * historique par JOUR au lieu d'une photo hebdomadaire.
 *
 * Deux temps :
 *   0. SITES FAVORIS — les domaines qui ont déjà rapporté un article retenu
 *      sont réinterrogés directement, sur tout le vocabulaire du métier. Un
 *      site qui a publié une fois publiera encore, et ce détour rattrape les
 *      articles dont le titre ne contient aucun des mots que nous suivons.
 *   1. COLLECTE — chaque source est lue, les articles sont notés selon leur
 *      ancrage béninois, leur rapport au métier et l'occasion qu'ils
 *      représentent. Ce tri ne dépend d'aucun service extérieur.
 *   2. TRI PAR MAKILA — le modèle local juge les mieux notés et dit POURQUOI.
 *      S'il est indisponible ou trop lent, la collecte reste valable : on ne
 *      perd jamais la veille faute d'IA.
 */
class VeilleOpportunites extends Command
{
    protected $signature = 'veille:opportunites
                            {--max=40 : Nombre d\'articles conservés}
                            {--sans-ia : Ne pas solliciter Makila}';

    protected $description = 'Collecte quotidienne des opportunités (Bénin d\'abord)';

    /**
     * Makila ne produit que 3 jetons par article : quelques secondes chacun.
     * Le plafond n'existe plus que comme garde-fou si une source explose.
     */
    private const MAX_JUGES_PAR_IA = 60;

    public function handle(): int
    {
        $mots = VitrineSetting::veilleMotsCles();
        $articles = [];

        foreach (VitrineSetting::veilleSources() as $source) {
            foreach ($this->lireFlux($source['url'] ?? '') as $item) {
                $lien = $item['lien'];
                // Un même article remonte souvent par plusieurs requêtes : on
                // garde la meilleure note plutôt que de le compter deux fois.
                [$note, $axes] = $this->noter($item['titre'], $mots);
                if (! isset($articles[$lien]) || $note > $articles[$lien]['note']) {
                    $articles[$lien] = $item + ['note' => $note, 'axes' => $axes, 'source' => $source['libelle'] ?? ''];
                }
            }
        }

        if ($articles === []) {
            $this->warn('Aucun article collecté — sources injoignables ?');

            return self::FAILURE;
        }

        // Un article sans ancrage béninois NI rapport au métier n'a rien à faire
        // dans une veille destinée à des artisans béninois.
        $retenus = collect($articles)
            ->filter(fn ($a) => $a['note'] > 0)
            ->sortByDesc('note')
            ->take((int) $this->option('max'))
            ->values();

        $this->info(sprintf('%d article(s) collecté(s), %d retenu(s).', count($articles), $retenus->count()));

        if (! $this->option('sans-ia')) {
            $retenus = $this->faireJugerParMakila($retenus);
        }

        $jour = now('Africa/Porto-Novo')->toDateString();
        $rang = 0;

        foreach ($retenus as $a) {
            $rang++;
            DB::table('gxt_veille_items')->updateOrInsert(
                ['semaine' => $jour, 'lien' => mb_substr($a['lien'], 0, 600)],
                [
                    'id'           => DB::table('gxt_veille_items')
                        ->where('semaine', $jour)->where('lien', $a['lien'])->value('id') ?? (string) Str::uuid(),
                    'titre'        => mb_substr($a['titre'], 0, 300),
                    'ia_selection' => (bool) ($a['ia_selection'] ?? false),
                    'ia_rang'      => min($rang, 50),
                    // Justification FACTUELLE, tirée des critères qui ont fait
                    // retenir l'article — vérifiable, contrairement à une phrase
                    // inventée par un petit modèle.
                    'ia_raison'    => empty($a['axes']) ? null
                        : mb_substr('Concerne ' . implode(', ', $a['axes']) . '.', 0, 600),
                    'created_at'   => now(),
                ],
            );
        }

        // Les sites qui ont rapporté sont réinterrogés directement aux
        // exécutions suivantes, sur tout le vocabulaire du métier : un titre
        // formulé autrement n'échappe plus à la veille faute de contenir le
        // mot exact que nous suivions.
        $domaines = $retenus
            ->filter(fn ($a) => ($a['domaine'] ?? '') !== '')
            ->mapWithKeys(fn ($a) => [$a['domaine'] => $a['nom_source'] ?? $a['domaine']])
            ->all();
        VitrineSetting::memoriserSitesFavoris($domaines);

        $this->info(sprintf('Veille du %s enregistrée : %d article(s).', $jour, $retenus->count()));
        $this->info(sprintf(
            '%d site(s) en mémoire, %d réinterrogé(s) à la prochaine exécution.',
            count(VitrineSetting::sitesFavoris()),
            count(VitrineSetting::recherchesSitesFavoris()),
        ));

        return self::SUCCESS;
    }

    /** Lit un flux RSS. Une source muette ne doit jamais faire tomber la veille. */
    private function lireFlux(string $url): array
    {
        if ($url === '') {
            return [];
        }

        try {
            $reponse = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'Gextimo-Veille/1.0'])
                ->get($url);

            if (! $reponse->successful()) {
                return [];
            }

            $xml = @simplexml_load_string($reponse->body());
            if (! $xml) {
                return [];
            }

            $items = [];
            foreach ($xml->channel->item ?? [] as $item) {
                $titre = trim((string) $item->title);
                $lien  = trim((string) $item->link);
                if ($titre === '' || $lien === '') {
                    continue;
                }

                // Le lien pointe vers news.google.com et ne dit rien de
                // l'éditeur. C'est la balise `<source url>` qui porte le vrai
                // domaine — celui qu'on veut retenir et réinterroger.
                $domaine = '';
                $nomSource = '';
                if (isset($item->source)) {
                    $nomSource = trim((string) $item->source);
                    $domaine = strtolower((string) parse_url((string) $item->source['url'], PHP_URL_HOST));
                    $domaine = preg_replace('/^www\./', '', $domaine) ?? '';
                }

                $items[] = [
                    'titre' => $titre,
                    'lien' => $lien,
                    'domaine' => $domaine,
                    'nom_source' => $nomSource,
                ];
            }

            return $items;
        } catch (\Throwable) {
            // Réseau capricieux ou flux mal formé : on passe à la source suivante.
            return [];
        }
    }

    /**
     * Note un titre. Le Bénin pèse le plus lourd : sans lui, même un article
     * « mode » très pertinent ailleurs ne vaut pas grand-chose ici.
     */
    private function noter(string $titre, array $mots): array
    {
        $t = mb_strtolower($titre);
        $contient = fn (array $liste) => count(array_filter($liste, fn ($m) => str_contains($t, $m)));

        $benin    = $contient($mots['benin'] ?? []);
        $metier   = $contient($mots['metier'] ?? []);
        $occasion = $contient($mots['occasion'] ?? []);

        // Un titre béninois SANS rapport au métier reste du bruit (politique,
        // sport…) : il faut au moins un des deux autres axes pour compter.
        if ($benin > 0 && $metier === 0 && $occasion === 0) {
            return [0, []];
        }

        $axes = [];
        if ($benin > 0)    { $axes[] = 'Bénin'; }
        if ($metier > 0)   { $axes[] = 'votre métier'; }
        if ($occasion > 0) { $axes[] = 'une occasion à saisir'; }

        return [($benin * 5) + ($metier * 2) + ($occasion * 3), $axes];
    }

    /**
     * Fait trancher Makila sur les mieux notés : article utile, oui ou non.
     *
     * Le tri par mots-clés dit « ça parle du Bénin et du métier » ; Makila dit
     * « ça sert vraiment à un artisan ». Il écarte ainsi ce qui coche les mots
     * sans rendre service — un match de foot au Bénin, un marché de Noël en
     * France. Indisponible, la collecte reste publiée : on ne perd jamais la
     * veille faute d'IA.
     */
    private function faireJugerParMakila($retenus)
    {
        $base = rtrim((string) config('services.ollama.url', 'http://127.0.0.1:11434'), '/');
        $modele = config('services.ollama.model');

        return $retenus->map(function ($a, $i) use ($base, $modele) {
            if ($i >= self::MAX_JUGES_PAR_IA) {
                return $a;
            }

            // Trois réglages, tous nécessaires — mesurés sur des titres dont on
            // connaissait la bonne réponse :
            //   « Reponse: » en amorce  — sans elle, le modèle commente au lieu
            //                             de répondre ;
            //   3 jetons au plus        — avec de la marge il brode, puis se
            //                             contredit (il avait rendu 0 sur 25) ;
            //   température 0           — même titre, même verdict d'un jour
            //                             à l'autre.
            // Ainsi réglé, il a classé correctement les 4 titres témoins.
            // Lui donner des exemples le dégrade (3 sur 4) : il se raccroche à
            // l'exemple le plus ressemblant au lieu de lire le titre.
            $invite = "Reponds OUI ou NON. "
                . "Cet article interesse-t-il un artisan ou createur de mode au Benin ?\n"
                . "Titre: {$a['titre']}\nReponse:";

            try {
                $r = Http::timeout(60)->post("{$base}/api/generate", [
                    'model'   => $modele,
                    'prompt'  => $invite,
                    'stream'  => false,
                    'options' => ['num_predict' => 3, 'temperature' => 0],
                ]);

                $texte = trim((string) ($r->json()['response'] ?? ''));
                if ($texte === '') {
                    return $a;
                }

                $a['ia_selection'] = str_contains(mb_strtoupper($texte), 'OUI');
            } catch (\Throwable) {
                // Makila indisponible : la collecte reste publiée telle quelle.
            }

            return $a;
        });
    }
}
