<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\NiveauConfig;
use App\Models\NotificationSysteme;
use App\Models\QuotaMensuel;
use App\Models\TransactionAbonnement;
use App\Services\PaymentService;
use App\Services\PointsFideliteService;
use App\Traits\ChecksPlanFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbonnementController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    // POST /api/abonnement/sponsoriser — achat d'une mise en avant vitrine (FedaPay).
    // Le prix dépend du nombre de jours (offres config-driven : VitrineSetting).
    public function sponsoriser(Request $request, PaymentService $paymentService): JsonResponse
    {
        $request->validate([
            'jours'      => ['required', 'integer', 'min:1', 'max:365'],
            'provider'   => ['sometimes', 'string', 'in:fedapay'],
            'return_url' => ['sometimes', 'nullable', 'url'],
        ]);

        $atelier  = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'sponsorisation')) {
            return $gate;
        }
        $provider = $request->provider ?? config('payment.default_provider', 'fedapay');

        $paiement = $paymentService->initiateSponsorisation(
            $atelier,
            (int) $request->jours,
            $provider,
            $request->return_url,
        );

        return response()->json([
            'paiement_id'  => $paiement->id,
            'checkout_url' => $paiement->checkout_url,
            'montant'      => $paiement->montant,
            'devise'       => $paiement->devise,
        ], 201);
    }

    public function plans(): JsonResponse
    {
        $plans = NiveauConfig::actif()->get([
            'cle', 'label', 'duree_jours', 'prix_xof',
            'prix_mensuel_equivalent_xof', 'description_courte', 'ordre_affichage', 'config',
        ]);

        return response()->json($plans);
    }

    /**
     * Récapitulatif AVANT paiement d'un changement de plan (spec upgrade 16/07/2026) :
     * crédit prorata du temps restant (base 31 j), montant réel à payer, nouvelle
     * échéance de date à date. À afficher tel quel côté app avant la validation.
     */
    public function upgradePreview(Request $request, PaymentService $paymentService): JsonResponse
    {
        $request->validate(['niveau_cle' => ['required', 'string', 'exists:niveaux_config,cle']]);

        $atelier = $this->getAtelier($request);
        $nouveau = NiveauConfig::where('cle', $request->niveau_cle)->where('is_actif', true)->firstOrFail();

        return response()->json($paymentService->previewChangement($atelier, $nouveau));
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

        // Quotas « par période » du plan gratuit (reset à l'anniversaire de l'abonnement) :
        // actes de publication vitrine + clients distincts facturés.
        $limits = app(\App\Services\AtelierLimitsService::class);
        $finPeriode = $abonnement->debutPeriodeCourante()->addMonthsNoOverflow(1)->toIso8601String();

        $quotaPublications = isset($config['publications_par_periode']) && $config['publications_par_periode'] !== null
            ? [
                'utilise'  => $limits->publicationsUtilisees($atelier),
                'max'      => (int) $config['publications_par_periode'],
                'reset_le' => $finPeriode,
            ]
            : null;

        $quotaClientsFactures = isset($config['max_clients_factures_periode']) && $config['max_clients_factures_periode'] !== null
            ? [
                'utilise'  => $limits->clientsFacturesPeriode($atelier)->count(),
                'max'      => (int) $config['max_clients_factures_periode'],
                'reset_le' => $finPeriode,
            ]
            : null;

        return response()->json([
            'niveau_cle'           => $abonnement->niveau_cle,
            'niveau_label'         => $abonnement->niveau?->label,
            'statut'               => $abonnement->statut,
            'jours_restants'       => $abonnement->timestamp_expiration
                ? max(0, (int) now()->diffInDays($abonnement->timestamp_expiration, false))
                : max(0, $abonnement->jours_restants),
            'timestamp_expiration' => $abonnement->timestamp_expiration?->toIso8601String(),
            'prix_xof'             => $abonnement->niveau?->prix_xof,
            'config'               => $config,
            'quota_factures'       => $quotaFactures,
            'quota_publications'      => $quotaPublications,
            'quota_clients_factures'  => $quotaClientsFactures,
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
            // Prolonger l'abonnement existant — snapshot mis à jour immédiatement
            $newExpire = $abonnement->timestamp_expiration->copy()->addDays($duree);
            $abonnement->update([
                'statut'               => 'actif',
                'jours_restants'       => $abonnement->jours_restants + $duree,
                'timestamp_expiration' => $newExpire,
                'niveau_cle'           => $transaction->niveau_cle,
                'config_snapshot'      => $niveau?->config,
            ]);
        } else {
            $abonnement = Abonnement::create([
                'atelier_id'           => $atelier->id,
                'niveau_cle'           => $transaction->niveau_cle,
                'statut'               => 'actif',
                'jours_restants'       => $duree,
                'timestamp_debut'      => $debut,
                'timestamp_expiration' => $expire,
                'config_snapshot'      => $niveau?->config,
            ]);
        }

        $atelier->update(['statut' => 'actif']);

        $transaction->update([
            'statut'     => 'utilise',
            'atelier_id' => $atelier->id,
            'utilise_at' => now(),
        ]);

        $niveauLabel = $niveau?->label ?? $transaction->niveau_cle;
        $config      = $niveau?->config ?? [];
        if (is_string($config)) {
            $config = json_decode($config, true) ?? [];
        }

        // Notification d'activation
        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => "Plan {$niveauLabel} activé !",
            'contenu'    => "Votre abonnement {$niveauLabel} est actif pour {$duree} jours. Bonne couture !",
            'type'       => 'abonnement_active',
            'lien'       => '/abonnement',
            'is_read'    => false,
        ]);

        // Message de bienvenue avec instructions selon le plan (#49)
        $instructions = $this->buildInstructions($config, $niveauLabel);
        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => "Bienvenue sur le plan {$niveauLabel}",
            'contenu'    => $instructions,
            'type'       => 'bienvenue_plan',
            'lien'       => '/abonnement',
            'is_read'    => false,
        ]);

        // Points fidélité à l'activation (#49)
        $ptsActivation = (int) ($config['pts_activation'] ?? 0);
        if ($ptsActivation > 0) {
            app(PointsFideliteService::class)->creditPoints(
                $atelier->id,
                'activation',
                $ptsActivation,
                "Activation plan {$niveauLabel}",
                $abonnement->id
            );
        }

        return response()->json([
            'message'        => "Abonnement activé ({$duree} jours).",
            'niveau_label'   => $niveauLabel,
            'duree_jours'    => $duree,
            'expiration'     => $abonnement->timestamp_expiration->toIso8601String(),
            'pts_credites'   => $ptsActivation,
        ]);
    }

    private function buildInstructions(array $config, string $niveauLabel): string
    {
        $lignes = ["Voici ce que vous pouvez faire avec votre plan {$niveauLabel} :"];

        $maxClients = $config['max_clients_par_mois'] ?? null;
        if ($maxClients && $maxClients !== -1) {
            $lignes[] = "• Jusqu'à {$maxClients} nouveaux clients par mois";
        } else {
            $lignes[] = "• Clients illimités";
        }

        $maxAssistants = (int) ($config['max_assistants'] ?? 0);
        if ($maxAssistants > 0) {
            $lignes[] = "• {$maxAssistants} assistant(s) autorisé(s)";
        }

        $maxSousAteliers = (int) ($config['max_sous_ateliers'] ?? 0);
        if ($maxSousAteliers > 0) {
            $lignes[] = "• {$maxSousAteliers} sous-atelier(s) disponible(s)";
        }

        if (!empty($config['facture_whatsapp'])) {
            $lignes[] = "• Envoi de factures par WhatsApp activé";
        }

        if (!empty($config['module_caisse'])) {
            $lignes[] = "• Module caisse activé";
        }

        if (!empty($config['sauvegarde_auto'])) {
            $lignes[] = "• Sauvegarde automatique activée";
        }

        $lignes[] = "Bonne utilisation !";

        return implode("\n", $lignes);
    }

}
