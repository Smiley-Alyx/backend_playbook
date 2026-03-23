<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class OrderView
{
    public function __construct(
        public string $id,
        public string $status,
        public int $amountMinor,
        public string $currency,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
