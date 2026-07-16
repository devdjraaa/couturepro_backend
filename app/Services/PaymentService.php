<?php

namespace App\Services;

use App\Contracts\PaymentProviderContract;
use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\NiveauConfig;
use App\Models\Paiement;
use App\Models\PatronAchat;
use App\Models\TransactionAbonnement;
use App\Models\VitrineSetting;
use App\Services\Payment\FedaPayProvider;
use App\Services\PointsFideliteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    private array $providers = [
        'fedapay' => FedaPayProvider::class,
    ];

    /**
     * Récapitulatif d'un changement/souscription de plan (spec upgrade, direction 16/07/2026).
     * Crédit prorata du temps restant : base FIXE de 31 jours quel que soit le mois —
     * crédit = jours_restants × (valeur mensuelle du plan actuel / 31), plancher 0 sur le
     * montant. Les plans annuels utilisent leur équivalent mensuel (base 31 uniforme).
     * L'essai et le plan gratuit n'ont pas de valeur financière → crédit 0.
     */
    public function previewChangement(Atelier $atelier, NiveauConfig $nouveau): array
    {
        $abonnement = Abonnement::where('atelier_id', $atelier->id)->first();
        $actuel     = $abonnement?->niveau;

        $actif = $abonnement
            && $abonnement->statut === 'actif'
            && $abonnement->timestamp_expiration?->isFuture();

        $renouvellement = $actif && $actuel && $actuel->cle === $nouveau->cle;
        $changement     = $actif && $actuel && $actuel->cle !== $nouveau->cle;

        $joursRestants = $actif
            ? (int) ceil(now()->diffInMinutes($abonnement->timestamp_expiration, false) / 1440)
            : 0;

        $credit = 0;
        if ($changement && ! $actuel->estPermanent() && (int) $actuel->prix_xof > 0) {
            $valeurMensuelle = (float) ($actuel->prix_mensuel_equivalent_xof ?: $actuel->prix_xof);
            $credit = (int) round(max(0, $joursRestants) * $valeurMensuelle / 31);
            $credit = min($credit, (int) $nouveau->prix_xof); // le montant ne devient jamais négatif
        }

        // Nouvelle période « de date à date » : un changement démarre immédiatement,
        // un renouvellement du même plan prolonge depuis l'échéance en cours.
        $base     = $renouvellement ? $abonnement->timestamp_expiration : now();
        $echeance = $nouveau->prochaineEcheance($base);

        return [
            'plan_actuel'         => $actuel?->label,
            'plan_actuel_cle'     => $actuel?->cle,
            'plan_nouveau'        => $nouveau->label,
            'plan_nouveau_cle'    => $nouveau->cle,
            'prix_nouveau'        => (int) $nouveau->prix_xof,
            'jours_restants'      => max(0, $joursRestants),
            'credit_prorata'      => $credit,
            'montant_a_payer'     => max(0, (int) $nouveau->prix_xof - $credit),
            'nouvelle_echeance'   => $echeance->toIso8601String(),
            'renouvellement'      => $renouvellement,
            'demarrage_immediat'  => ! $renouvellement,
        ];
    }

    public function initiate(Atelier $atelier, string $niveauCle, string $provider = 'fedapay', ?string $returnUrl = null): Paiement
    {
        $niveau = NiveauConfig::where('cle', $niveauCle)->where('is_actif', true)->firstOrFail();

        // Crédit prorata du plan en cours (spec upgrade) : on ne facture que la différence.
        $recap   = $this->previewChangement($atelier, $niveau);
        $montant = $recap['montant_a_payer'];

        // Rien à encaisser (plan gratuit, ou crédit couvrant tout le nouveau plan) :
        // activation directe, sans passer par le provider. FedaPay rejette un montant
        // nul (« amount doit être > 0 »), ce qui provoquait un 500.
        if ($montant <= 0) {
            $paiement = Paiement::create([
                'atelier_id'   => $atelier->id,
                'niveau_cle'   => $niveau->cle,
                'duree_jours'  => $niveau->duree_jours,
                'montant'      => 0,
                'meta'         => ['recap_upgrade' => $recap],
                'devise'       => 'XOF',
                'provider'     => $provider,
                'statut'       => 'completed',
                'initiated_at' => now(),
                'completed_at' => now(),
                'ip_address'   => request()->ip(),
            ]);

            $this->activerAbonnement($atelier->id, $niveau->cle, $niveau->duree_jours);

            return $paiement->fresh();
        }

        $paiement = Paiement::create([
            'atelier_id'   => $atelier->id,
            'niveau_cle'   => $niveau->cle,
            'duree_jours'  => $niveau->duree_jours,
            'montant'      => $montant,
            'meta'         => ['recap_upgrade' => $recap],
            'devise'       => 'XOF',
            'provider'     => $provider,
            'statut'       => 'pending',
            'initiated_at' => now(),
            'expires_at'   => now()->addHours(2),
            'ip_address'   => request()->ip(),
        ]);

        $providerInstance = $this->resolveProvider($provider);
        $proprietaire     = $atelier->proprietaire;

        $result = $providerInstance->initiate($paiement, [
            'email'      => $proprietaire->email,
            'nom'        => $proprietaire->nom,
            'prenom'     => $proprietaire->prenom,
            'return_url' => $returnUrl,
        ]);

        $paiement->update([
            'checkout_url'            => $result->checkoutUrl,
            'provider_transaction_id' => $result->providerTransactionId,
            'provider_metadata'       => $result->providerMetadata,
        ]);

        return $paiement->fresh();
    }

    /**
     * Achat de mise en avant (sponsorisation) : prix config-driven, durée libre.
     * Réutilise le même provider de paiement que l'abonnement.
     */
    public function initiateSponsorisation(Atelier $atelier, int $jours, string $provider = 'fedapay', ?string $returnUrl = null): Paiement
    {
        $config = VitrineSetting::sponsorisation();

        abort_unless((bool) ($config['actif'] ?? false), 422, "La sponsorisation n'est pas disponible actuellement.");

        $offre = collect($config['offres'] ?? [])->firstWhere('jours', $jours);
        abort_unless($offre, 422, 'Offre de sponsorisation invalide.');

        $paiement = Paiement::create([
            'atelier_id'   => $atelier->id,
            'type'         => 'sponsorisation',
            'meta'         => ['jours' => $jours],
            // niveau_cle est requis (FK) : on conserve le plan courant à titre indicatif.
            'niveau_cle'   => $atelier->abonnement?->niveau_cle ?? NiveauConfig::query()->value('cle'),
            'duree_jours'  => $jours,
            'montant'      => (int) $offre['prix'],
            'devise'       => 'XOF',
            'provider'     => $provider,
            'statut'       => 'pending',
            'initiated_at' => now(),
            'expires_at'   => now()->addHours(2),
            'ip_address'   => request()->ip(),
        ]);

        $providerInstance = $this->resolveProvider($provider);
        $proprietaire     = $atelier->proprietaire;

        $result = $providerInstance->initiate($paiement, [
            'email'      => $proprietaire->email,
            'nom'        => $proprietaire->nom,
            'prenom'     => $proprietaire->prenom,
            'return_url' => $returnUrl,
        ]);

        $paiement->update([
            'checkout_url'            => $result->checkoutUrl,
            'provider_transaction_id' => $result->providerTransactionId,
            'provider_metadata'       => $result->providerMetadata,
        ]);

        return $paiement->fresh();
    }

    /**
     * P161-163 : achat d'un patron par un visiteur. Réutilise le même provider FedaPay.
     * L'atelier vendeur porte le paiement (atelier_id) ; l'acheteur (anonyme) fournit ses
     * coordonnées, transmises au provider pour le reçu.
     */
    public function initiatePatron(PatronAchat $achat, ?string $returnUrl = null, string $provider = 'fedapay'): Paiement
    {
        $patron  = $achat->patron;
        $atelier = $patron->atelier;

        $paiement = Paiement::create([
            'atelier_id'   => $atelier->id,
            'type'         => 'patron',
            'meta'         => ['patron_achat_id' => $achat->id],
            // niveau_cle est requis (FK) : plan courant du vendeur à titre indicatif.
            'niveau_cle'   => $atelier->abonnement?->niveau_cle ?? NiveauConfig::query()->value('cle'),
            'duree_jours'  => 0,
            'montant'      => $achat->montant,
            'devise'       => 'XOF',
            'provider'     => $provider,
            'statut'       => 'pending',
            'initiated_at' => now(),
            'expires_at'   => now()->addHours(2),
            'ip_address'   => request()->ip(),
        ]);

        $result = $this->resolveProvider($provider)->initiate($paiement, [
            'email'      => $achat->acheteur_email ?: ($atelier->proprietaire->email ?? 'client@gextimo.africa'),
            'nom'        => $achat->acheteur_nom,
            'prenom'     => '',
            'return_url' => $returnUrl,
        ]);

        $paiement->update([
            'checkout_url'            => $result->checkoutUrl,
            'provider_transaction_id' => $result->providerTransactionId,
            'provider_metadata'       => $result->providerMetadata,
        ]);

        $achat->update(['paiement_id' => $paiement->id]);

        return $paiement->fresh();
    }

    private function activerPatron(Paiement $paiement): void
    {
        DB::transaction(function () use ($paiement) {
            $achatId = $paiement->meta['patron_achat_id'] ?? null;
            $achat   = $achatId ? PatronAchat::find($achatId) : null;

            if ($achat && $achat->statut !== 'paye') {
                $achat->update(['statut' => 'paye', 'paye_at' => now()]);
            }

            $paiement->update(['statut' => 'completed', 'completed_at' => now()]);
        });
    }

    private function activerSponsorisation(Paiement $paiement): void
    {
        DB::transaction(function () use ($paiement) {
            $atelier = Atelier::find($paiement->atelier_id);
            $jours   = (int) ($paiement->meta['jours'] ?? $paiement->duree_jours);

            // Prolonge si déjà sponsorisé, sinon part de maintenant.
            $base = ($atelier->sponsor_jusqu_a && $atelier->sponsor_jusqu_a->isFuture())
                ? $atelier->sponsor_jusqu_a->copy()
                : now();

            $atelier->update(['sponsor_jusqu_a' => $base->addDays($jours)]);

            $paiement->update([
                'statut'       => 'completed',
                'completed_at' => now(),
            ]);
        });
    }

    public function activate(Paiement $paiement): void
    {
        if ($paiement->type === 'sponsorisation') {
            $this->activerSponsorisation($paiement);
            return;
        }

        if ($paiement->type === 'patron') {
            $this->activerPatron($paiement);
            return;
        }

        DB::transaction(function () use ($paiement) {
            $code = TransactionAbonnement::create([
                'code_transaction' => Str::upper(Str::random(16)),
                'atelier_id'       => $paiement->atelier_id,
                'paiement_id'      => $paiement->id,
                'niveau_cle'       => $paiement->niveau_cle,
                'duree_jours'      => $paiement->duree_jours,
                'montant'          => $paiement->montant,
                'devise'           => $paiement->devise,
                'canal'            => 'webhook',
                'statut'           => 'utilise',
                'utilise_at'       => now(),
            ]);

            $this->activerAbonnement($paiement->atelier_id, $paiement->niveau_cle, $paiement->duree_jours);

            $paiement->update([
                'statut'       => 'completed',
                'completed_at' => now(),
            ]);
        });
    }

    /**
     * Marque le paiement comme remboursé en base et expire l'abonnement.
     * FedaPay ne propose pas d'API REST de remboursement — l'admin doit
     * effectuer le remboursement manuellement depuis le dashboard FedaPay.
     * Retourne true si tout s'est bien passé, false si un avertissement doit être affiché.
     */
    public function refund(Paiement $paiement): bool
    {
        DB::transaction(function () use ($paiement) {
            $paiement->update(['statut' => 'refunded']);

            $abonnement = Abonnement::where('atelier_id', $paiement->atelier_id)->first();
            if ($abonnement && $abonnement->statut === 'actif') {
                $abonnement->update(['statut' => 'expire']);
                Atelier::where('id', $paiement->atelier_id)->update(['statut' => 'expire']);
            }
        });

        return true;
    }

    public function handleRetour(string $provider, Paiement $paiement): void
    {
        $providerInstance = $this->resolveProvider($provider);
        $status           = $providerInstance->checkTransactionStatus($paiement->provider_transaction_id);

        if ($status === 'completed') {
            $this->activate($paiement);
        } elseif (in_array($status, ['failed', 'refunded'])) {
            $paiement->update(['statut' => $status]);
        }
    }

    public function handleWebhook(string $provider, string $rawPayload, string $signature): void
    {
        $providerInstance = $this->resolveProvider($provider);

        if (!$providerInstance->verifyWebhookSignature($rawPayload, $signature)) {
            abort(403, 'Signature webhook invalide.');
        }

        $webhookPayload = $providerInstance->parseWebhookPayload(json_decode($rawPayload, true));

        $paiement = Paiement::where('provider', $provider)
            ->where('provider_transaction_id', $webhookPayload->providerTransactionId)
            ->where('statut', 'pending')
            ->first();

        if (!$paiement) {
            return;
        }

        $paiement->update([
            'webhook_received_at' => now(),
            'provider_metadata'   => array_merge(
                $paiement->provider_metadata ?? [],
                ['webhook' => $webhookPayload->rawData]
            ),
        ]);

        match ($webhookPayload->status) {
            'completed' => $this->activate($paiement),
            'failed'    => $paiement->update(['statut' => 'failed']),
            'refunded'  => $paiement->update(['statut' => 'refunded']),
            default     => null,
        };
    }

    private function activerAbonnement(string $atelierId, string $niveauCle, int $dureeJours): void
    {
        $atelier     = Atelier::find($atelierId);
        $niveau      = NiveauConfig::where('cle', $niveauCle)->first();
        $abonnement  = Abonnement::where('atelier_id', $atelierId)->first();

        // Normalise le config (peut être double-encodé depuis le seeder)
        $configSnapshot = $niveau?->config;
        if (is_string($configSnapshot)) {
            $configSnapshot = json_decode($configSnapshot, true);
        }

        // Échéance « de date à date » (spec upgrade, direction 16/07/2026) :
        //  - renouvellement du MÊME plan encore actif → prolonge depuis l'échéance en cours ;
        //  - changement de plan (upgrade — le temps restant a été crédité au paiement) ou
        //    nouveau départ → démarre immédiatement, échéance au même jour du mois/an suivant.
        $debut  = now();
        $actif  = $abonnement
            && $abonnement->statut === 'actif'
            && $abonnement->timestamp_expiration?->isFuture();

        $base       = ($actif && $abonnement->niveau_cle === $niveauCle) ? $abonnement->timestamp_expiration : $debut;
        $expiration = $niveau
            ? $niveau->prochaineEcheance($base)
            : $base->copy()->addDays($dureeJours);

        $joursRestants = max(0, (int) ceil($debut->diffInMinutes($expiration, false) / 1440));

        if ($abonnement) {
            $abonnement->update([
                'niveau_cle'           => $niveauCle,
                'statut'               => 'actif',
                'jours_restants'       => $joursRestants,
                'timestamp_debut'      => $debut,
                'timestamp_expiration' => $expiration,
                'config_snapshot'      => $configSnapshot,
            ]);
        } else {
            Abonnement::create([
                'atelier_id'           => $atelierId,
                'niveau_cle'           => $niveauCle,
                'statut'               => 'actif',
                'jours_restants'       => $joursRestants,
                'timestamp_debut'      => $debut,
                'timestamp_expiration' => $expiration,
                'config_snapshot'      => $configSnapshot,
            ]);
        }

        $atelier?->update(['statut' => 'actif']);

        // P48 : un abonnement couvre TOUS les ateliers du propriétaire (le multi-ateliers
        // est inclus dans le plan Studio). On propage le même plan/échéance aux autres
        // ateliers du proprio, dans la limite de sous-ateliers du plan.
        if ($atelier && $niveau) {
            $this->propagerAuxSousAteliers($atelier, $niveauCle, $configSnapshot, $debut, $expiration, $joursRestants);
        }

        // Step 5 blueprint : créditer pts_activation
        $ptsActivation = (int) ($configSnapshot['pts_activation'] ?? 0);
        if ($ptsActivation > 0) {
            app(PointsFideliteService::class)->creditPoints(
                $atelierId,
                'abonnement_activation',
                $ptsActivation,
                "Activation abonnement {$niveauCle}",
            );
        }

        // P49 : message de bienvenue après souscription — points crédités + instructions
        // du plan (description_courte de niveaux_config → éditable admin, zéro hardcoding).
        $contenu = trim(
            ($niveau?->description_courte ? $niveau->description_courte . ' ' : '')
            . ($ptsActivation > 0 ? "{$ptsActivation} points de fidélité crédités." : '')
        ) ?: 'Votre abonnement est actif.';

        \App\Models\NotificationSysteme::create([
            'atelier_id' => $atelierId,
            'titre'      => 'Bienvenue sur le plan ' . ($niveau?->label ?? $niveauCle) . ' !',
            'contenu'    => $contenu,
            'type'       => 'abonnement_active',
            'lien'       => '/parametres?tab=abonnement',
            'is_read'    => false,
        ]);
    }

    /**
     * P48 : propage le plan actif à tous les autres ateliers du même propriétaire
     * (le plan Studio inclut le multi-ateliers). Limité au nombre de sous-ateliers
     * autorisé par le plan ; les sous-ateliers en trop restent tels quels.
     */
    private function propagerAuxSousAteliers(
        Atelier $source,
        string $niveauCle,
        ?array $configSnapshot,
        \Carbon\CarbonInterface $debut,
        \Carbon\CarbonInterface $expiration,
        int $joursRestants,
    ): void {
        $maxSous = $configSnapshot['max_sous_ateliers'] ?? 0;
        if (! $source->proprietaire_id) {
            return;
        }

        $autres = Atelier::where('proprietaire_id', $source->proprietaire_id)
            ->where('id', '!=', $source->id)
            ->orderBy('created_at')
            ->take((int) $maxSous)
            ->get();

        foreach ($autres as $sous) {
            Abonnement::updateOrCreate(
                ['atelier_id' => $sous->id],
                [
                    'niveau_cle'           => $niveauCle,
                    'statut'               => 'actif',
                    'jours_restants'       => $joursRestants,
                    'timestamp_debut'      => $debut,
                    'timestamp_expiration' => $expiration,
                    'config_snapshot'      => $configSnapshot,
                ]
            );
            $sous->update(['statut' => 'actif']);
        }
    }

    private function resolveProvider(string $provider): PaymentProviderContract
    {
        $class = $this->providers[$provider] ?? null;

        if (!$class) {
            throw new \InvalidArgumentException("Provider de paiement inconnu : {$provider}");
        }

        return app($class);
    }
}
