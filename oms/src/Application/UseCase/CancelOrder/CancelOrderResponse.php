<?php

declare(strict_types=1);

namespace App\Application\UseCase\CancelOrder;

use App\Application\DTO\OrderView;

final readonly class CancelOrderResponse
{
    public function __construct(public OrderView $order)
    {
    }
}
