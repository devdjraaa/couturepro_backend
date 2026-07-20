<?php

namespace App\Console\Commands;

use App\Services\MetaStatsService;
use Illuminate\Console\Command;

/**
 * MVP réseaux sociaux — collecte quotidienne des statistiques de la Page
 * Facebook (lecture seule, API officielle). Planifiée chaque jour ; peut se
 * lancer à la main après une publication importante : chaque passage ajoute un
 * relevé horodaté, l'historique montre la progression du post.
 */
class CollecterStatsReseaux extends Command
{
    protected $signature = 'reseaux:collecter {--limite=25 : Nombre de posts récents à relever}';

    protected $description = 'Relève les statistiques des publications des pages officielles (Meta Graph API, lecture seule)';

    public function handle(MetaStatsService $service): int
    {
        $res = $service->collecterFacebook((int) $this->option('limite'));

        if (isset($res['erreur'])) {
            $this->warn('Facebook : ' . $res['erreur']);

            // Non configuré = état normal tant que la direction n'a pas fourni le
            // jeton : ne pas transformer le scheduler en source d'alertes rouges.
            return str_contains($res['erreur'], 'non configuré') ? self::SUCCESS : self::FAILURE;
        }

        $this->info("Facebook : {$res['posts']} nouveau(x) post(s), {$res['releves']} relevé(s).");

        return self::SUCCESS;
    }
}
