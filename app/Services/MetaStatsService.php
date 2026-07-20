<?php

namespace App\Services;

use App\Models\VitrineSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MVP réseaux sociaux (direction, 20/07) — collecte des statistiques de NOTRE
 * Page Facebook via l'API OFFICIELLE Meta Graph. Lecture seule : aucune
 * publication, aucune réponse automatique, aucun scraping (exigence explicite
 * de la direction — le scraping fait bannir la page).
 *
 * Configuration dans `VitrineSetting` clé `reseaux_sociaux` :
 *   { facebook: { page_id, token, actif } }
 * Le jeton est un jeton de PAGE longue durée (issu d'un jeton utilisateur
 * longue durée, il n'expire pas tant que le mot de passe/les droits ne
 * changent pas). En cas d'expiration, l'erreur est journalisée et visible
 * dans le statut admin — la collecte ne casse rien d'autre.
 */
class MetaStatsService
{
    private const GRAPH = 'https://graph.facebook.com/v21.0';

    public static function config(): array
    {
        $cfg = VitrineSetting::where('cle', 'reseaux_sociaux')->value('valeur');

        return array_merge(
            ['facebook' => ['page_id' => null, 'token' => null, 'actif' => false, 'derniere_erreur' => null]],
            is_array($cfg) ? $cfg : []
        );
    }

    /**
     * Collecte les posts récents de la Page et enregistre un relevé de stats
     * pour chacun. Idempotent : un post déjà connu est retrouvé par son id
     * externe ; chaque passage ajoute UN relevé horodaté.
     *
     * @return array{posts:int, releves:int}|array{erreur:string}
     */
    public function collecterFacebook(int $limite = 25): array
    {
        $cfg = self::config()['facebook'];
        if (empty($cfg['actif']) || empty($cfg['page_id']) || empty($cfg['token'])) {
            return ['erreur' => 'Facebook non configuré (page_id/token manquants ou inactif).'];
        }

        $rep = Http::timeout(30)->get(self::GRAPH . '/' . $cfg['page_id'] . '/published_posts', [
            'access_token' => $cfg['token'],
            'limit'        => $limite,
            'fields'       => implode(',', [
                'id', 'created_time', 'message', 'permalink_url',
                'attachments{media_type}',
                'shares',
                'likes.summary(true).limit(0)',
                'comments.summary(true).limit(0)',
                'insights.metric(post_impressions,post_impressions_unique,post_clicks){name,values}',
            ]),
        ]);

        if ($rep->failed()) {
            $erreur = $rep->json('error.message') ?? ('HTTP ' . $rep->status());
            $this->memoriserErreur($erreur);
            Log::warning('[reseaux] collecte Facebook échouée', ['erreur' => $erreur]);

            return ['erreur' => $erreur];
        }

        $this->memoriserErreur(null);

        $posts = 0;
        $releves = 0;
        foreach ($rep->json('data', []) as $p) {
            $insights = collect($p['insights']['data'] ?? [])
                ->mapWithKeys(fn ($m) => [$m['name'] => (int) ($m['values'][0]['value'] ?? 0)]);

            $postId = DB::table('reseaux_posts')->where('plateforme', 'facebook')
                ->where('externe_id', $p['id'])->value('id');

            if (! $postId) {
                $postId = (string) Str::uuid();
                DB::table('reseaux_posts')->insert([
                    'id'         => $postId,
                    'plateforme' => 'facebook',
                    'externe_id' => $p['id'],
                    'publie_at'  => isset($p['created_time']) ? date('Y-m-d H:i:s', strtotime($p['created_time'])) : null,
                    'format'     => $this->format($p),
                    'extrait'    => isset($p['message']) ? mb_substr($p['message'], 0, 300) : null,
                    'permalink'  => $p['permalink_url'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $posts++;
            }

            DB::table('reseaux_stats')->insert([
                'id'           => (string) Str::uuid(),
                'post_id'      => $postId,
                'releve_at'    => now(),
                'portee'       => $insights['post_impressions_unique'] ?? 0,
                'impressions'  => $insights['post_impressions'] ?? 0,
                'reactions'    => (int) ($p['likes']['summary']['total_count'] ?? 0),
                'commentaires' => (int) ($p['comments']['summary']['total_count'] ?? 0),
                'partages'     => (int) ($p['shares']['count'] ?? 0),
                'clics'        => $insights['post_clicks'] ?? 0,
            ]);
            $releves++;
        }

        return ['posts' => $posts, 'releves' => $releves];
    }

    /**
     * Le rapport demandé par la direction : les meilleurs posts d'une période
     * et ce qu'ils ont en commun (heure, format, sujet). Se base sur le DERNIER
     * relevé de chaque post.
     */
    public function rapport(?string $depuis = null, int $top = 5): array
    {
        $depuis = $depuis ?: now()->startOfMonth()->toDateString();

        $posts = DB::table('reseaux_posts as p')
            ->join('reseaux_stats as s', 's.id', '=', DB::raw(
                '(select s2.id from reseaux_stats s2 where s2.post_id = p.id order by s2.releve_at desc limit 1)'
            ))
            ->where('p.publie_at', '>=', $depuis)
            ->select('p.id', 'p.publie_at', 'p.format', 'p.sujet', 'p.extrait', 'p.permalink',
                's.portee', 's.impressions', 's.reactions', 's.commentaires', 's.partages', 's.clics', 's.releve_at')
            ->orderByDesc('s.portee')
            ->get();

        $meilleurs = $posts->take($top);

        return [
            'periode_depuis' => $depuis,
            'nb_posts'       => $posts->count(),
            'top'            => $meilleurs->values(),
            'points_communs' => [
                'heures'  => $meilleurs->groupBy(fn ($p) => date('H', strtotime($p->publie_at)) . 'h')
                    ->map->count()->sortDesc(),
                'formats' => $meilleurs->groupBy('format')->map->count()->sortDesc(),
                'sujets'  => $meilleurs->whereNotNull('sujet')->groupBy('sujet')->map->count()->sortDesc(),
            ],
        ];
    }

    private function format(array $post): string
    {
        $type = $post['attachments']['data'][0]['media_type'] ?? null;

        return match ($type) {
            'photo'  => 'photo',
            'video'  => 'video',
            'album'  => 'album',
            'link'   => 'lien',
            null     => 'texte',
            default  => strtolower($type),
        };
    }

    private function memoriserErreur(?string $erreur): void
    {
        $cfg = self::config();
        $cfg['facebook']['derniere_erreur'] = $erreur;
        $cfg['facebook']['derniere_collecte'] = now()->toIso8601String();
        VitrineSetting::updateOrCreate(['cle' => 'reseaux_sociaux'], ['valeur' => $cfg]);
    }
}
