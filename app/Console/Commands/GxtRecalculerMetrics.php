<?php

namespace App\Console\Commands;

use App\Models\Atelier;
use App\Models\GxtClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// P202 / Espace Client v3 — Phase 4 : recalcul nocturne des synthèses client & designer.
// Source de vérité : gxt_evenements + commandes/avis/gxt_reclamations. Barèmes = spec direction.
class GxtRecalculerMetrics extends Command
{
    protected $signature = 'gxt:recalculer-metrics';

    protected $description = 'Recalcule gxt_client_metrics (engagement/segment/RFM/CLV) et gxt_designer_metrics (confiance/revenus) — P202';

    public function handle(): int
    {
        $clients = 0;
        GxtClient::query()->chunkById(200, function ($lot) use (&$clients) {
            foreach ($lot as $client) {
                $this->syntheseClient($client);
                $clients++;
            }
        });

        $designers = 0;
        Atelier::where('type', 'designer')->where('is_demo', false)->chunkById(100, function ($lot) use (&$designers) {
            foreach ($lot as $atelier) {
                $this->syntheseDesigner($atelier);
                $designers++;
            }
        });

        $this->info("Synthèses recalculées : {$clients} clients, {$designers} designers.");

        return self::SUCCESS;
    }

    // ── Client ───────────────────────────────────────────────────────────────

    private function syntheseClient(GxtClient $client): void
    {
        $events = DB::table('gxt_evenements')
            ->where('gxt_client_id', $client->id)
            ->selectRaw('type, count(*) as n')
            ->groupBy('type')->pluck('n', 'type');

        $commandes = DB::table('commandes')
            ->where('gxt_client_id', $client->id)->whereNull('deleted_at')
            ->selectRaw("count(*) as total,
                         sum(case when statut = 'livre' then 1 else 0 end) as livrees,
                         coalesce(sum(case when statut = 'livre' then prix else 0 end), 0) as montant,
                         max(created_at) as derniere, min(created_at) as premiere")
            ->first();

        $avis = (int) DB::table('avis')->where('gxt_client_id', $client->id)->count();
        $reclamationsResolues = (int) DB::table('gxt_reclamations')
            ->where('gxt_client_id', $client->id)->where('statut', 'resolue')->count();

        // Barème engagement (spec) : +1 visite, +5 panier, +10 avis, +15 réclamation résolue, +20 achat.
        $visites = (int) DB::table('gxt_evenements')->where('gxt_client_id', $client->id)
            ->distinct()->count('session_id');
        $engagement = $visites
            + 5  * (int) ($events['ajout_panier'] ?? 0)
            + 10 * $avis
            + 15 * $reclamationsResolues
            + 20 * (int) $commandes->livrees;

        $segment = match (true) {
            $engagement > 100 => 'vip',
            $engagement > 50  => 'chaud',
            $engagement > 20  => 'tiede',
            default           => 'froid',
        };

        // Intérêt par catégorie (barème spec : vue +1, temps>30s +3, wishlist +5, panier +10, achat +20).
        $interets = [];
        DB::table('gxt_evenements')->where('gxt_client_id', $client->id)
            ->whereNotNull('metadata')
            ->get(['type', 'metadata', 'duree_secondes'])
            ->each(function ($e) use (&$interets) {
                $cat = json_decode($e->metadata, true)['categorie'] ?? null;
                if (! $cat) {
                    return;
                }
                $pts = match ($e->type) {
                    'vue_article', 'vue_article_repete' => 1 + (($e->duree_secondes ?? 0) > 30 ? 3 : 0),
                    'ajout_wishlist'                    => 5,
                    'ajout_panier'                      => 10,
                    'commande_passee'                   => 20,
                    default                             => 0,
                };
                $interets[$cat] = ($interets[$cat] ?? 0) + $pts;
            });
        arsort($interets);

        // RFM (échelles simples 1-5) + CLV + churn.
        $recenceJours = $commandes->derniere ? now()->diffInDays($commandes->derniere) : null;
        $r = $recenceJours === null ? 1 : match (true) {
            $recenceJours <= 14 => 5, $recenceJours <= 30 => 4, $recenceJours <= 60 => 3, $recenceJours <= 90 => 2, default => 1,
        };
        $f = (int) $commandes->livrees >= 5 ? 5 : max(1, (int) $commandes->livrees);
        $m = match (true) {
            $commandes->montant >= 500000 => 5, $commandes->montant >= 200000 => 4,
            $commandes->montant >= 100000 => 3, $commandes->montant >= 25000 => 2, default => 1,
        };
        $rfmSegment = match (true) {
            $r >= 4 && $f >= 4          => 'vip',
            $r >= 4 && $f <= 2 && $m >= 4 => 'gros_acheteur_ponctuel',
            $r <= 2 && $f <= 2          => 'inactif',
            default                     => 'standard',
        };

        $dureeRelation = $commandes->premiere ? max(1, (int) now()->diffInDays($commandes->premiere)) : 0;
        $churn = match (true) {
            (int) $commandes->livrees > 0 && $recenceJours > 90 => 'eleve',
            (int) $commandes->livrees > 0 && $recenceJours > 30 => 'moyen',
            default                                             => 'faible',
        };

        DB::table('gxt_client_metrics')->updateOrInsert(
            ['gxt_client_id' => $client->id],
            [
                'id'               => DB::table('gxt_client_metrics')->where('gxt_client_id', $client->id)->value('id') ?? (string) Str::uuid(),
                'engagement_score' => $engagement,
                'segment'          => $segment,
                'interest_scores'  => json_encode($interets),
                'rfm'              => json_encode(['r' => $r, 'f' => $f, 'm' => $m, 'segment' => $rfmSegment]),
                'clv'              => json_encode([
                    'montant_total'        => (float) $commandes->montant,
                    'nb_commandes'         => (int) $commandes->livrees,
                    'duree_relation_jours' => $dureeRelation,
                    'frequence_achat_jours' => (int) $commandes->livrees > 1 ? intdiv($dureeRelation, (int) $commandes->livrees) : null,
                ]),
                'preferences'      => json_encode(['categories_top3' => array_slice(array_keys($interets), 0, 3)]),
                'risque_churn'     => $churn,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]
        );
    }

    // ── Designer ─────────────────────────────────────────────────────────────

    private function syntheseDesigner(Atelier $atelier): void
    {
        $c = DB::table('commandes')->where('atelier_id', $atelier->id)->whereNull('deleted_at')
            ->selectRaw("count(*) as total,
                         sum(case when statut = 'livre' then 1 else 0 end) as livrees,
                         sum(case when statut = 'annule' then 1 else 0 end) as annulees,
                         sum(case when statut = 'livre' and date_livraison_prevue is not null
                                   and date_livraison_effective is not null
                                   and date_livraison_effective::date <= date_livraison_prevue then 1 else 0 end) as a_temps,
                         avg(case when statut = 'livre' and date_livraison_effective is not null
                                  then extract(epoch from (date_livraison_effective - created_at)) / 86400 end) as delai_moyen")
            ->first();

        $note = (float) (DB::table('avis')->where('atelier_id', $atelier->id)->where('statut', 'valide')->avg('note') ?? 0);
        $reclamations = (int) DB::table('gxt_reclamations')->where('atelier_id', $atelier->id)->count();

        $livrees = max(1, (int) $c->livrees);
        $total   = max(1, (int) $c->total);
        $tauxTemps       = round(100 * (int) $c->a_temps / $livrees, 1);
        $tauxReclamation = round(100 * $reclamations / $total, 1);
        $tauxAnnulation  = round(100 * (int) $c->annulees / $total, 1);

        // Score /100 (pondération : livraison à temps 35 %, note 30 %, réclamations 20 %, annulations 15 %).
        $score = (int) round(
            0.35 * $tauxTemps
            + 0.30 * ($note / 5 * 100)
            + 0.20 * max(0, 100 - 4 * $tauxReclamation)
            + 0.15 * max(0, 100 - 2 * $tauxAnnulation)
        );

        // Revenus : mois en cours / précédent + projection prorata du mois.
        $moisEnCours = (float) DB::table('commandes')->where('atelier_id', $atelier->id)
            ->where('statut', 'livre')->where('created_at', '>=', now()->startOfMonth())->sum('prix');
        $moisPrecedent = (float) DB::table('commandes')->where('atelier_id', $atelier->id)
            ->where('statut', 'livre')
            ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->startOfMonth()])
            ->sum('prix');
        $prediction = now()->day > 2
            ? round($moisEnCours / now()->day * now()->daysInMonth, 2)
            : $moisPrecedent;
        $croissance = $moisPrecedent > 0 ? round(100 * ($prediction - $moisPrecedent) / $moisPrecedent, 1) : null;

        DB::table('gxt_designer_metrics')->updateOrInsert(
            ['atelier_id' => $atelier->id],
            [
                'id'               => DB::table('gxt_designer_metrics')->where('atelier_id', $atelier->id)->value('id') ?? (string) Str::uuid(),
                'score_confiance'  => $score,
                'confiance_details' => json_encode([
                    'taux_livraison_temps' => $tauxTemps,
                    'taux_reclamation'     => $tauxReclamation,
                    'note_moyenne'         => round($note, 2),
                    'delai_moyen_jours'    => $c->delai_moyen !== null ? round((float) $c->delai_moyen, 1) : null,
                    'taux_annulation'      => $tauxAnnulation,
                ]),
                'revenus'          => json_encode([
                    'mois_en_cours'  => $moisEnCours,
                    'mois_precedent' => $moisPrecedent,
                    'prediction'     => $prediction,
                    'croissance_pct' => $croissance,
                ]),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]
        );
    }
}
