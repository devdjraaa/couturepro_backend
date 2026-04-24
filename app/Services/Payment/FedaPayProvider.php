<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderContract;
use App\DTOs\PaymentInitiationResult;
use App\DTOs\WebhookPayload;
use App\Models\Paiement;
use Illuminate\Support\Facades\Http;

class FedaPayProvider implements PaymentProviderContract
{
    private string $apiKey;
    private string $baseUrl;
    private string $webhookSecret;

    public function __construct()
    {
        $this->apiKey        = config('payment.fedapay.api_key');
        $this->webhookSecret = config('payment.fedapay.webhook_secret');
        $this->baseUrl       = config('payment.fedapay.sandbox')
            ? 'https://sandbox-api.fedapay.com/v1'
            : 'https://api.fedapay.com/v1';
    }

    public function initiate(Paiement $paiement, array $customerData): PaymentInitiationResult
    {
        // Étape 1 : créer la transaction
        $response = Http::withToken($this->apiKey)
            ->asJson()
            ->post("{$this->baseUrl}/transactions", [
                'description' => "Abonnement CouturePro — {$paiement->niveau_cle}",
                'amount'      => (int) $paiement->montant,
                'currency'    => ['iso' => $paiement->devise],
                'customer'    => [
                    'email'     => $customerData['email'],
                    'firstname' => $customerData['prenom'] ?? '',
                    'lastname'  => $customerData['nom'] ?? '',
                ],
                'callback_url' => config('payment.fedapay.callback_url'),
            ]);

        $response->throw();

        $transaction   = $response->json()['v1/transaction'] ?? [];
        $transactionId = (string) ($transaction['id'] ?? '');

        // Étape 2 : générer le token de paiement (POST requis par FedaPay)
        $tokenResponse = Http::withToken($this->apiKey)
            ->asJson()
            ->post("{$this->baseUrl}/transactions/{$transactionId}/token");

        $tokenResponse->throw();

        $checkoutUrl = $tokenResponse->json('url');

        return new PaymentInitiationResult(
            checkoutUrl:           $checkoutUrl,
            providerTransactionId: $transactionId,
            providerMetadata:      $transaction,
        );
    }

    public function verifyWebhookSignature(string $rawPayload, string $_signature): bool
    {
        // FedaPay ne signe pas les webhooks avec HMAC.
        // On vérifie en re-fetchant la transaction directement auprès de leur API.
        $payload       = json_decode($rawPayload, true);
        $transaction   = $payload['v1/transaction'] ?? $payload['entity'] ?? [];
        $transactionId = $transaction['id'] ?? null;

        if (!$transactionId) {
            return false;
        }

        try {
            $status = $this->checkTransactionStatus((string) $transactionId);
            return in_array($status, ['completed', 'failed', 'refunded']);
        } catch (\Throwable) {
            return false;
        }
    }

    public function parseWebhookPayload(array $payload): WebhookPayload
    {
        $transaction = $payload['v1/transaction'] ?? $payload['entity'] ?? [];
        $fedaStatus  = $transaction['status'] ?? 'unknown';

        $status = match ($fedaStatus) {
            'approved' => 'completed',
            'declined', 'canceled' => 'failed',
            'refunded' => 'refunded',
            default    => 'failed',
        };

        return new WebhookPayload(
            providerTransactionId: (string) ($transaction['id'] ?? ''),
            status:                $status,
            rawData:               $payload,
        );
    }

    public function checkTransactionStatus(string $providerTransactionId): string
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/transactions/{$providerTransactionId}");

        $response->throw();

        $fedaStatus = ($response->json()['v1/transaction']['status'] ?? null) ?? 'unknown';

        return match ($fedaStatus) {
            'approved' => 'completed',
            'declined', 'canceled' => 'failed',
            'refunded' => 'refunded',
            default    => 'pending',
        };
    }
}
