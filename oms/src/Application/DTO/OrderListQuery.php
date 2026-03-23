<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class OrderListQuery
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $status,
    ) {
    }
}
