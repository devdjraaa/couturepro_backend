<?php

namespace App\Console\Commands;

use App\Models\Paiement;
use App\Services\PaymentService;
use App\Services\Payment\FedaPayProvider;
use Illuminate\Console\Command;

class CheckPendingPayments extends Command
{
    protected $signature   = 'payments:check-pending';
    protected $description = 'Vérifie l\'état des paiements pending auprès des providers (fallback si webhook raté)';

    public function __construct(private PaymentService $paymentService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $paiements = Paiement::pending()
            ->whereNotNull('provider_transaction_id')
            ->where('initiated_at', '<', now()->subMinutes(15))
            ->where('expires_at', '>', now())
            ->get();

        foreach ($paiements as $paiement) {
            try {
                $provider = $this->resolveProvider($paiement->provider);
                $status   = $provider->checkTransactionStatus($paiement->provider_transaction_id);

                if ($status === 'completed') {
                    $this->paymentService->activate($paiement);
                    $this->info("Paiement {$paiement->id} activé via polling.");
                } elseif ($status === 'failed') {
                    $paiement->update(['statut' => 'failed']);
                }
            } catch (\Throwable $e) {
                $this->error("Erreur paiement {$paiement->id} : {$e->getMessage()}");
            }
        }
    }

    private function resolveProvider(string $provider): \App\Contracts\PaymentProviderContract
    {
        return match ($provider) {
            'fedapay' => app(FedaPayProvider::class),
            default   => throw new \InvalidArgumentException("Provider inconnu : $provider"),
        };
    }
}
