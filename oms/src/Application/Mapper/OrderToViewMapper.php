<?php

declare(strict_types=1);

namespace App\Application\Mapper;

use App\Application\DTO\OrderView;
use App\Domain\Order\Order;

final class OrderToViewMapper
{
    public function map(Order $order): OrderView
    {
        return new OrderView(
            id: $order->id()->toString(),
            status: $order->status()->value,
            amountMinor: $order->amountMinor(),
            currency: $order->currency(),
            createdAt: $order->createdAt()->format(DATE_RFC3339),
            updatedAt: $order->updatedAt()->format(DATE_RFC3339),
        );
    }
}
