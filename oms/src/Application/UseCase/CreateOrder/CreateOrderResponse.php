<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateOrder;

use App\Application\DTO\OrderView;

final readonly class CreateOrderResponse
{
    public function __construct(public OrderView $order)
    {
    }
}
