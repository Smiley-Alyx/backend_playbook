<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListOrders;

use App\Application\DTO\OrderListResult;

final readonly class ListOrdersResponse
{
    public function __construct(public OrderListResult $result)
    {
    }
}
