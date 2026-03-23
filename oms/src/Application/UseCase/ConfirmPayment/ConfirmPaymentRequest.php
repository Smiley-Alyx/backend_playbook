<?php

declare(strict_types=1);

namespace App\Application\UseCase\ConfirmPayment;

use DateTimeImmutable;

final readonly class ConfirmPaymentRequest
{
    public function __construct(
        public string $orderId,
        public DateTimeImmutable $now,
    ) {
    }
}
