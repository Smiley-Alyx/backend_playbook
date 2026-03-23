<?php

declare(strict_types=1);

namespace App\Application\UseCase\RefundOrder;

use App\Application\DTO\OrderView;

final readonly class RefundOrderResponse
{
    public function __construct(public OrderView $order)
    {
    }
}
