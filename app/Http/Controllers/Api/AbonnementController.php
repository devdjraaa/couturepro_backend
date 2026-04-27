<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\NiveauConfig;
use App\Models\QuotaMensuel;
use App\Models\TransactionAbonnement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbonnementController extends Controller
{
    public function plans(): JsonResponse
    {
        $plans = NiveauConfig::actif()->get([
            'cle', 'label', 'duree_jours', 'prix_xof',
            'prix_mensuel_equivalent_xof', 'description_courte', 'ordre_affichage', 'config',
        ]);

        return response()->json($plans);
    }

    public function current(Request $request): JsonResponse
    {
        $atelier    = $this->getAtelier($request);
        $abonnement = Abonnement::where('atelier_id', $atelier->id)
            ->with('niveau')
            ->latest('timestamp_debut')
            ->first();

        if (!$abonnement) {
            return response()->json(null);
        }

        // Auto-expirer si la date est passée
        if (
            in_array($abonnement->statut, ['actif', 'essai'])
            && $abonnement->timestamp_expiration?->isPast()
        ) {
            $abonnement->update(['statut' => 'expire']);
            $atelier->update(['statut' => 'expire']);
            $abonnement->refresh();
        }

        $config = $abonnement->getConfigEffective();

        $quotaFactures = null;
        if (!empty($config['facture_whatsapp'])) {
            $quota        = QuotaMensuel::courant($atelier->id);
            $maxFact      = isset($config['max_factures_par_mois']) ? (int) $config['max_factures_par_mois'] : null;
            $quotaFactures = [
                'utilise' => $quota->nb_factures_envoyees,
                'max'     => $maxFact, // null = illimité
            ];
        }

        return response()->json([
            'niveau_cle'           => $abonnement->niveau_cle,
            'niveau_label'         => $abonnement->niveau?->label,
            'statut'               => $abonnement->statut,
            'jours_restants'       => max(0, $abonnement->jours_restants),
            'timestamp_expiration' => $abonnement->timestamp_expiration?->toIso8601String(),
            'prix_xof'             => $abonnement->niveau?->prix_xof,
            'config'               => $config,
            'quota_factures'       => $quotaFactures,
        ]);
    }

    public function activerCode(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:12']]);

        $atelier     = $this->getAtelier($request);
        $code        = strtoupper(trim($request->code));

        $transaction = TransactionAbonnement::where('code_transaction', $code)
            ->where('statut', 'actif')
            ->whereNull('utilise_at')
            ->where(function ($q) use ($atelier) {
                $q->whereNull('atelier_id')->orWhere('atelier_id', $atelier->id);
            })
            ->first();

        if (!$transaction) {
            return response()->json(['message' => 'Code invalide, déjà utilisé ou non applicable à votre atelier.'], 422);
        }

        $niveau = NiveauConfig::where('cle', $transaction->niveau_cle)->first();
        $duree  = $transaction->duree_jours;

        // Prolonger ou créer l'abonnement
        $abonnement = Abonnement::where('atelier_id', $atelier->id)
            ->latest('timestamp_debut')
            ->first();

        $debut  = now();
        $expire = $debut->copy()->addDays($duree);

        if ($abonnement && in_array($abonnement->statut, ['actif', 'essai'])) {
            // Prolonger l'abonnement existant
            $newExpire = $abonnement->timestamp_expiration->copy()->addDays($duree);
            $abonnement->update([
                'statut'               => 'actif',
                'jours_restants'       => $abonnement->jours_restants + $duree,
                'timestamp_expiration' => $newExpire,
                'niveau_cle'           => $transaction->niveau_cle,
            ]);
        } else {
            $abonnement = Abonnement::create([
                'atelier_id'           => $atelier->id,
                'niveau_cle'           => $transaction->niveau_cle,
                'statut'               => 'actif',
                'jours_restants'       => $duree,
                'timestamp_debut'      => $debut,
                'timestamp_expiration' => $expire,
            ]);
        }

        $atelier->update(['statut' => 'actif']);

        $transaction->update([
            'statut'     => 'utilise',
            'atelier_id' => $atelier->id,
            'utilise_at' => now(),
        ]);

        return response()->json([
            'message'      => "Abonnement activé ({$duree} jours).",
            'niveau_label' => $niveau?->label ?? $transaction->niveau_cle,
            'duree_jours'  => $duree,
            'expiration'   => $abonnement->timestamp_expiration->toIso8601String(),
        ]);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        return $user instanceof EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
