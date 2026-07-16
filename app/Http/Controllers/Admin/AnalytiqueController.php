<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

// P202 / Espace Client v3 — Phase 5 : tableau de bord analytique interne (NOVAFRIQ).
// 4 vues (spec direction) : globale / clients / designers / tendances. Lit les tables de
// synthèse recalculées la nuit (gxt_client_metrics, gxt_designer_metrics) + compteurs légers :
// aucun calcul lourd à l'affichage.
class AnalytiqueController extends Controller
{
    /** GET /admin/analytique — les 4 vues d'un coup (payload compact). */
    public function index(): JsonResponse
    {
        return response()->json([
            'globale'   => $this->vueGlobale(),
            'clients'   => $this->vueClients(),
            'designers' => $this->vueDesigners(),
            'tendances' => $this->vueTendances(),
        ]);
    }

    private function vueGlobale(): array
    {
        $aujourdhui = now()->startOfDay();
        $semaine    = now()->startOfWeek();
        $mois       = now()->startOfMonth();

        $visiteurs = fn ($depuis) => (int) DB::table('gxt_evenements')
            ->where('created_at', '>=', $depuis)->distinct()->count('session_id');

        $commandesVitrine = DB::table('commandes')->where('source', 'vitrine')->whereNull('deleted_at');

        return [
            'visiteurs' => [
                'aujourdhui' => $visiteurs($aujourdhui),
                'semaine'    => $visiteurs($semaine),
                'mois'       => $visiteurs($mois),
            ],
            'inscriptions_mois'  => (int) DB::table('gxt_clients')->where('created_at', '>=', $mois)->count(),
            'clients_total'      => (int) DB::table('gxt_clients')->count(),
            'commandes'          => [
                'mois'        => (int) (clone $commandesVitrine)->where('created_at', '>=', $mois)->count(),
                'valeur_mois' => (float) (clone $commandesVitrine)->where('created_at', '>=', $mois)->sum('prix'),
                'total'       => (int) (clone $commandesVitrine)->count(),
            ],
            'taux_conversion_mois' => $this->tauxConversion($mois),
        ];
    }

    private function vueClients(): array
    {
        $m = DB::table('gxt_client_metrics');

        return [
            'segments'      => (clone $m)->selectRaw('segment, count(*) as n')->groupBy('segment')->pluck('n', 'segment'),
            'vip'           => DB::table('gxt_client_metrics')->join('gxt_clients', 'gxt_clients.id', '=', 'gxt_client_metrics.gxt_client_id')
                ->where('segment', 'vip')->orderByDesc('engagement_score')->limit(20)
                ->get(['gxt_clients.email', 'gxt_clients.prenom', 'gxt_clients.nom', 'engagement_score', 'clv']),
            'churn_eleve'   => DB::table('gxt_client_metrics')->join('gxt_clients', 'gxt_clients.id', '=', 'gxt_client_metrics.gxt_client_id')
                ->where('risque_churn', 'eleve')->limit(20)
                ->get(['gxt_clients.email', 'gxt_clients.prenom', 'gxt_clients.nom', 'segment', 'clv']),
            'paniers_abandonnes_7j' => (int) DB::table('gxt_evenements')->where('type', 'abandon_panier')
                ->where('created_at', '>=', now()->subDays(7))->count(),
            'recherches_sans_resultat' => DB::table('gxt_recherches_sans_resultat')
                ->orderByDesc('nombre_fois')->limit(20)->get(['terme', 'nombre_fois', 'updated_at']),
        ];
    }

    private function vueDesigners(): array
    {
        $base = DB::table('gxt_designer_metrics')
            ->join('ateliers', 'ateliers.id', '=', 'gxt_designer_metrics.atelier_id')
            ->select('ateliers.id', 'ateliers.nom', 'score_confiance', 'confiance_details', 'revenus');

        return [
            'classement_revenus' => (clone $base)->orderByDesc(DB::raw("(revenus->>'mois_en_cours')::numeric"))->limit(15)->get(),
            'scores_confiance'   => (clone $base)->orderByDesc('score_confiance')->limit(15)->get(),
            'alertes_score_bas'  => (clone $base)->where('score_confiance', '<', 50)->where('score_confiance', '>', 0)->get(),
            'sans_commande_15j'  => DB::table('ateliers')->where('type', 'designer')->where('is_demo', false)
                ->whereNotIn('id', DB::table('commandes')->where('created_at', '>=', now()->subDays(15))->whereNull('deleted_at')->pluck('atelier_id'))
                ->limit(30)->pluck('nom'),
        ];
    }

    private function vueTendances(): array
    {
        $mois = now()->startOfMonth();

        return [
            'categories_top' => DB::table('gxt_evenements')
                ->where('created_at', '>=', $mois)
                ->whereIn('type', ['vue_article', 'vue_article_repete'])
                ->selectRaw("metadata->>'categorie' as categorie, count(*) as n")
                ->whereNotNull(DB::raw("metadata->>'categorie'"))
                ->groupBy(DB::raw("metadata->>'categorie'"))->orderByDesc('n')->limit(10)->pluck('n', 'categorie'),
            'heures_pointe' => DB::table('gxt_evenements')
                ->where('created_at', '>=', now()->subDays(30))
                ->selectRaw('extract(hour from created_at) as heure, count(*) as n')
                ->groupBy(DB::raw('extract(hour from created_at)'))->orderByDesc('n')->limit(6)->pluck('n', 'heure'),
            'canaux_acquisition' => DB::table('gxt_clients')
                ->selectRaw("coalesce(nullif(utm_source, ''), 'direct') as source, count(*) as n")
                ->groupBy(DB::raw("coalesce(nullif(utm_source, ''), 'direct')"))->orderByDesc('n')->pluck('n', 'source'),
            'revenus_6_mois' => DB::table('commandes')->where('source', 'vitrine')->where('statut', 'livre')
                ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
                ->selectRaw("to_char(created_at, 'YYYY-MM') as mois, coalesce(sum(prix),0) as total")
                ->groupBy(DB::raw("to_char(created_at, 'YYYY-MM')"))->orderBy('mois')->pluck('total', 'mois'),
        ];
    }

    private function tauxConversion(\DateTimeInterface $depuis): ?float
    {
        $sessions = (int) DB::table('gxt_evenements')->where('created_at', '>=', $depuis)->distinct()->count('session_id');
        if ($sessions === 0) {
            return null;
        }
        $achats = (int) DB::table('commandes')->where('source', 'vitrine')->where('created_at', '>=', $depuis)->whereNull('deleted_at')->count();

        return round(100 * $achats / $sessions, 2);
    }
}
