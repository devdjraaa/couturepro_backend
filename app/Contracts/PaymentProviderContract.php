<?php

namespace App\Contracts;

use App\DTOs\PaymentInitiationResult;
use App\DTOs\WebhookPayload;
use App\Models\Paiement;

interface PaymentProviderContract
{
    public function initiate(Paiement $paiement, array $customerData): PaymentInitiationResult;

    public function verifyWebhookSignature(string $rawPayload, string $_signature): bool;

    public function parseWebhookPayload(array $payload): WebhookPayload;

    public function checkTransactionStatus(string $providerTransactionId): string;

    public function refund(string $providerTransactionId): void;
}
