<?php

declare(strict_types=1);

namespace App\Application\DTO;

final readonly class OrderListResult
{
    /**
     * @var list<OrderView>
     */
    public array $items;

    /**
     * @param list<OrderView> $items
     */
    public function __construct(
        array $items,
        public int $page,
        public int $perPage,
        public int $total,
    ) {
        $this->items = $items;
    }
}
