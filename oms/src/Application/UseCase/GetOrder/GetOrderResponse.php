<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetOrder;

use App\Application\DTO\OrderView;

final readonly class GetOrderResponse
{
    public function __construct(public OrderView $order)
    {
    }
}
