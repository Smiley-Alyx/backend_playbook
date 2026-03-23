<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListOrders;

final readonly class ListOrdersRequest
{
    public function __construct(
        public int $page,
        public int $perPage,
        public ?string $status,
    ) {
    }
}
