<?php

declare(strict_types=1);

namespace App\Application\UseCase\RefundOrder;

use DateTimeImmutable;

final readonly class RefundOrderRequest
{
    public function __construct(
        public string $orderId,
        public DateTimeImmutable $now,
    ) {
    }
}
