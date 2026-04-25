<?php

namespace App\Services;

use App\Contracts\PaymentProviderContract;
use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\NiveauConfig;
use App\Models\Paiement;
use App\Models\TransactionAbonnement;
use App\Services\Payment\FedaPayProvider;
use App\Services\PointsFideliteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    private array $providers = [
        'fedapay' => FedaPayProvider::class,
    ];

    public function initiate(Atelier $atelier, string $niveauCle, string $provider = 'fedapay'): Paiement
    {
        $niveau = NiveauConfig::where('cle', $niveauCle)->where('is_actif', true)->firstOrFail();

        $paiement = Paiement::create([
            'atelier_id'   => $atelier->id,
            'niveau_cle'   => $niveau->cle,
            'duree_jours'  => $niveau->duree_jours,
            'montant'      => $niveau->prix_xof,
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
            'email'  => $proprietaire->email,
            'nom'    => $proprietaire->nom,
            'prenom' => $proprietaire->prenom,
        ]);

        $paiement->update([
            'checkout_url'            => $result->checkoutUrl,
            'provider_transaction_id' => $result->providerTransactionId,
            'provider_metadata'       => $result->providerMetadata,
        ]);

        return $paiement->fresh();
    }

    public function activate(Paiement $paiement): void
    {
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

        $debut       = now();
        $expiration  = now()->addDays($dureeJours);

        if ($abonnement) {
            // Proroger si encore actif, sinon repartir de zéro
            if ($abonnement->statut === 'actif' && $abonnement->timestamp_expiration?->isFuture()) {
                $expiration = $abonnement->timestamp_expiration->addDays($dureeJours);
                $dureeJours = (int) $abonnement->jours_restants + $dureeJours;
            }

            $abonnement->update([
                'niveau_cle'           => $niveauCle,
                'statut'               => 'actif',
                'jours_restants'       => $dureeJours,
                'timestamp_debut'      => $debut,
                'timestamp_expiration' => $expiration,
                'config_snapshot'      => $configSnapshot,
            ]);
        } else {
            Abonnement::create([
                'atelier_id'           => $atelierId,
                'niveau_cle'           => $niveauCle,
                'statut'               => 'actif',
                'jours_restants'       => $dureeJours,
                'timestamp_debut'      => $debut,
                'timestamp_expiration' => $expiration,
                'config_snapshot'      => $configSnapshot,
            ]);
        }

        $atelier?->update(['statut' => 'actif']);

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
