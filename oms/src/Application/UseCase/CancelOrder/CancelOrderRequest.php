<?php

declare(strict_types=1);

namespace App\Application\UseCase\CancelOrder;

use DateTimeImmutable;

final readonly class CancelOrderRequest
{
    public function __construct(
        public string $orderId,
        public DateTimeImmutable $now,
    ) {
    }
}
