<?php

namespace App\DTOs;

readonly class PaymentInitiationResult
{
    public function __construct(
        public string  $checkoutUrl,
        public string  $providerTransactionId,
        public array   $providerMetadata = [],
    ) {}
}
