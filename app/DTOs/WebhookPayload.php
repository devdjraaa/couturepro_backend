<?php

namespace App\DTOs;

readonly class WebhookPayload
{
    public function __construct(
        public string $providerTransactionId,
        public string $status,           // 'completed' | 'failed' | 'refunded'
        public array  $rawData = [],
    ) {}
}
