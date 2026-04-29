<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandePaiement;
use App\Traits\ResolvesAtelier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaisseController extends Controller
{
    use ResolvesAtelier;

    /**
     * Stats financières du mois (ou du mois passé en paramètre).
     * GET /caisse/stats?mois=2026-04
     */
    public function stats(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $mois    = $request->input('mois', now()->format('Y-m'));

        [$annee, $num] = explode('-', $mois);
        $debut = Carbon::create((int) $annee, (int) $num, 1)->startOfMonth();
        $fin   = $debut->copy()->endOfMonth();

        // Encaissements du mois
        $paiements = CommandePaiement::where('atelier_id', $atelier->id)
            ->whereBetween('created_at', [$debut, $fin])
            ->get(['montant', 'mode_paiement']);

        $totalEncaisse = $paiements->sum('montant');

        $modes = $paiements->groupBy('mode_paiement')
            ->map(fn ($g) => (float) $g->sum('montant'));

        // Commandes actives (non annulées, non archivées)
        $commandes = Commande::where('atelier_id', $atelier->id)
            ->where('statut', '!=', 'annule')
            ->where('is_archived', false)
            ->get(['prix', 'acompte', 'statut']);

        $totalEnAttente = $commandes
            ->where('statut', 'en_cours')
            ->sum(fn ($c) => max(0, (float) $c->prix - (float) $c->acompte));

        $nbSoldees = $commandes
            ->filter(fn ($c) => $c->statut === 'livre' || (float) $c->acompte >= (float) $c->prix)
            ->count();

        $nbEnCours = $commandes->where('statut', 'en_cours')->count();

        return response()->json([
            'mois'              => $mois,
            'total_encaisse'    => (float) $totalEncaisse,
            'total_en_attente'  => (float) $totalEnAttente,
            'nb_commandes_soldees'  => $nbSoldees,
            'nb_commandes_en_cours' => $nbEnCours,
            'modes_paiement'    => $modes,
        ]);
    }

    /**
     * Classement des clients par solde restant dû (toutes commandes actives).
     * GET /caisse/clients
     */
    public function clients(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $clients = Client::where('atelier_id', $atelier->id)
            ->where('is_archived', false)
            ->withSum(
                ['commandes as total_prix' => fn ($q) => $q->where('statut', '!=', 'annule')->where('is_archived', false)],
                'prix'
            )
            ->withSum(
                ['commandes as total_acompte' => fn ($q) => $q->where('statut', '!=', 'annule')->where('is_archived', false)],
                'acompte'
            )
            ->withCount(
                ['commandes as nb_commandes' => fn ($q) => $q->where('statut', '!=', 'annule')->where('is_archived', false)]
            )
            ->having('total_prix', '>', 0)
            ->get()
            ->map(fn ($c) => [
                'id'             => $c->id,
                'nom'            => $c->nom,
                'prenom'         => $c->prenom,
                'telephone'      => $c->telephone,
                'total_commande' => (float) ($c->total_prix    ?? 0),
                'total_paye'     => (float) ($c->total_acompte ?? 0),
                'solde_restant'  => max(0, (float) ($c->total_prix ?? 0) - (float) ($c->total_acompte ?? 0)),
                'nb_commandes'   => (int) $c->nb_commandes,
            ])
            ->sortByDesc('solde_restant')
            ->values();

        return response()->json($clients);
    }
}
