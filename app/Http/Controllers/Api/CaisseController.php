<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandePaiement;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaisseController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    public function stats(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($gate = $this->planGate($atelier, 'module_caisse')) {
            return $gate;
        }
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
     * PL-3 : rapport mensuel complet (données pour le PDF généré côté app).
     * Global (encaissé, en attente, modes, nb commandes) + détail par cliente
     * (commandes, payé, solde) sur le mois demandé. Gaté `rapport_mensuel`.
     */
    public function rapportMensuel(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'rapport_mensuel')) {
            return $gate;
        }

        $mois = $request->input('mois', now()->format('Y-m'));
        [$annee, $num] = array_map('intval', explode('-', $mois));
        $debut = Carbon::create($annee, $num, 1)->startOfMonth();
        $fin   = $debut->copy()->endOfMonth();

        // Encaissements du mois, par cliente (via la commande liée).
        $paiements = CommandePaiement::where('commande_paiements.atelier_id', $atelier->id)
            ->whereBetween('commande_paiements.created_at', [$debut, $fin])
            ->join('commandes', 'commandes.id', '=', 'commande_paiements.commande_id')
            ->leftJoin('clients', 'clients.id', '=', 'commandes.client_id')
            ->get([
                'commande_paiements.montant',
                'commande_paiements.mode_paiement',
                'commande_paiements.created_at',
                'clients.id as client_id',
                'clients.nom as client_nom',
                'clients.prenom as client_prenom',
            ]);

        $totalEncaisse = (float) $paiements->sum('montant');
        $modes = $paiements->groupBy('mode_paiement')->map(fn ($g) => (float) $g->sum('montant'));

        // Commandes créées dans le mois.
        $commandesMois = Commande::where('atelier_id', $atelier->id)
            ->where('statut', '!=', 'annule')
            ->where('is_archived', false)
            ->whereBetween('created_at', [$debut, $fin])
            ->get(['prix', 'acompte', 'statut', 'client_id']);

        // Détail par cliente : encaissé ce mois + nb commandes du mois.
        $parCliente = $paiements
            ->groupBy('client_id')
            ->map(function ($grp) use ($commandesMois) {
                $first = $grp->first();
                $nom = trim("{$first->client_prenom} {$first->client_nom}");

                return [
                    'client'        => $nom !== '' ? $nom : 'Client supprimé',
                    'encaisse'      => (float) $grp->sum('montant'),
                    'nb_paiements'  => $grp->count(),
                    'nb_commandes'  => $commandesMois->where('client_id', $first->client_id)->count(),
                ];
            })
            ->sortByDesc('encaisse')
            ->values();

        return response()->json([
            'mois'            => $mois,
            'atelier'         => $atelier->nom,
            'total_encaisse'  => $totalEncaisse,
            'nb_paiements'    => $paiements->count(),
            'nb_commandes'    => $commandesMois->count(),
            'total_facture'   => (float) $commandesMois->sum('prix'),
            'modes_paiement'  => $modes,
            'par_cliente'     => $parCliente,
        ]);
    }

    public function clients(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($gate = $this->planGate($atelier, 'module_caisse')) {
            return $gate;
        }

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
            // ⚠️ PAS de `having('total_prix', ...)` : cet alias vient d'une
            // sous-requête `withSum`. MySQL le tolère, PostgreSQL le REFUSE
            // (SQLSTATE 42703) — la production tournant sur PostgreSQL, cette
            // liste répondait 500 alors qu'elle passait en développement.
            // Le filtre se fait sur la collection, déjà parcourue juste après.
            ->get()
            ->filter(fn ($c) => (float) ($c->total_prix ?? 0) > 0)
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
