<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateOrder;

use DateTimeImmutable;

final readonly class CreateOrderRequest
{
    public function __construct(
        public int $amountMinor,
        public string $currency,
        public DateTimeImmutable $now,
    ) {
    }
}
