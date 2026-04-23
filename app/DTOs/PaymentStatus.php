<?php

namespace App\DTOs;

readonly class PaymentStatus
{
    public function __construct(
        public string  $paiementId,
        public string  $statut,
        public ?string $checkoutUrl,
        public ?string $completedAt,
    ) {}
}
