<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandePaiement;
use App\Traits\ResolvesAtelier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    use ResolvesAtelier;

    // GET /dashboard — stats du tableau de bord avec cache 60s
    public function index(Request $request): JsonResponse
    {
        $atelier  = $this->getAtelier($request);
        $cacheKey = "dashboard_{$atelier->id}";

        // Force refresh si param ?refresh=1
        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        $data = Cache::remember($cacheKey, 60, fn () => $this->buildStats($atelier));

        return response()->json($data);
    }

    // GET /dashboard/multi — stats consolidées de tous les ateliers
    public function multi(Request $request): JsonResponse
    {
        $proprietaire = $request->user();
        $atelierIds   = Atelier::where('proprietaire_id', $proprietaire->id)->pluck('id');

        $filtre = $request->query('atelier_id');
        if ($filtre && $atelierIds->contains($filtre)) {
            $atelier  = Atelier::find($filtre);
            $cacheKey = "dashboard_{$atelier->id}";
            if ($request->boolean('refresh')) {
                Cache::forget($cacheKey);
            }
            return response()->json(Cache::remember($cacheKey, 60, fn () => $this->buildStats($atelier)));
        }

        // Vue consolidée
        $stats = $atelierIds->map(function ($id) use ($request) {
            $atelier  = Atelier::with('abonnement')->find($id);
            $cacheKey = "dashboard_{$id}";
            if ($request->boolean('refresh')) {
                Cache::forget($cacheKey);
            }
            return Cache::remember($cacheKey, 60, fn () => $this->buildStats($atelier));
        });

        return response()->json([
            'ateliers'     => $stats,
            'totaux'       => [
                'clients'           => $stats->sum('clients.total'),
                'commandes_en_cours'=> $stats->sum('commandes.en_cours'),
                'revenu_mois'       => $stats->sum('finances.encaisse_mois'),
            ],
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    private function buildStats(Atelier $atelier): array
    {
        $moisDebut = now()->startOfMonth();
        $moisFin   = now()->endOfMonth();
        $today     = now()->toDateString();

        // Clients
        $totalClients    = Client::where('atelier_id', $atelier->id)->where('is_archived', false)->count();
        $nouveauxCeMois  = Client::where('atelier_id', $atelier->id)
            ->whereBetween('created_at', [$moisDebut, $moisFin])
            ->count();
        $nbVip           = Client::where('atelier_id', $atelier->id)->where('is_vip', true)->count();

        // Commandes
        $commandesQuery  = Commande::where('atelier_id', $atelier->id)->where('is_archived', false);
        $enCours         = (clone $commandesQuery)->where('statut', 'en_cours')->count();
        $livrees         = (clone $commandesQuery)->where('statut', 'livre')->count();
        $annulees        = (clone $commandesQuery)->where('statut', 'annule')->count();
        $urgentes        = (clone $commandesQuery)->where('statut', 'en_cours')->where('urgence', true)->count();
        $enRetard        = (clone $commandesQuery)
            ->where('statut', 'en_cours')
            ->whereNotNull('date_livraison_prevue')
            ->where('date_livraison_prevue', '<', $today)
            ->count();
        $livraisonAujourd = (clone $commandesQuery)
            ->where('statut', 'en_cours')
            ->where('date_livraison_prevue', $today)
            ->count();
        $commandesCeMois  = (clone $commandesQuery)
            ->whereBetween('created_at', [$moisDebut, $moisFin])
            ->count();

        // Finances
        $encaisseMois = CommandePaiement::where('atelier_id', $atelier->id)
            ->whereBetween('created_at', [$moisDebut, $moisFin])
            ->sum('montant');

        $resteAEncaisser = Commande::where('atelier_id', $atelier->id)
            ->where('statut', 'en_cours')
            ->where('is_archived', false)
            ->selectRaw('SUM(prix - acompte) as total')
            ->value('total') ?? 0;

        // Abonnement
        $abonnement  = $atelier->abonnement;
        $joursRestants = $abonnement?->timestamp_expiration
            ? max(0, (int) now()->diffInDays($abonnement->timestamp_expiration, false))
            : null;

        return [
            'atelier_id'   => $atelier->id,
            'atelier_nom'  => $atelier->nom,
            'synced_at'    => now()->toIso8601String(),
            'clients'      => [
                'total'          => $totalClients,
                'nouveaux_mois'  => $nouveauxCeMois,
                'vip'            => $nbVip,
            ],
            'commandes'    => [
                'en_cours'           => $enCours,
                'livrees'            => $livrees,
                'annulees'           => $annulees,
                'urgentes'           => $urgentes,
                'en_retard'          => $enRetard,
                'livraison_aujd'     => $livraisonAujourd,
                'nouvelles_ce_mois'  => $commandesCeMois,
            ],
            'finances'     => [
                'encaisse_mois'      => (float) $encaisseMois,
                'reste_a_encaisser'  => (float) max(0, $resteAEncaisser),
            ],
            'abonnement'   => $abonnement ? [
                'niveau_cle'    => $abonnement->niveau_cle,
                'statut'        => $abonnement->statut,
                'jours_restants'=> $joursRestants,
            ] : null,
        ];
    }
}
