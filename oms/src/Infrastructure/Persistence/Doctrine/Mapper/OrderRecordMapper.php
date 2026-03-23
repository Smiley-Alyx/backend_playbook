<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\Order\Order;
use App\Domain\Order\OrderId;
use App\Domain\Order\OrderStatus;
use App\Infrastructure\Persistence\Doctrine\Entity\OrderRecord;

final class OrderRecordMapper
{
    public function toDomain(OrderRecord $record): Order
    {
        return Order::reconstitute(
            id: OrderId::fromString($record->id()),
            amountMinor: $record->amountMinor(),
            currency: $record->currency(),
            status: OrderStatus::from($record->status()),
            createdAt: $record->createdAt(),
            updatedAt: $record->updatedAt(),
        );
    }

    public function toRecord(Order $order, ?OrderRecord $existing): OrderRecord
    {
        if ($existing === null) {
            return OrderRecord::new(
                id: $order->id()->toString(),
                status: $order->status()->value,
                amountMinor: $order->amountMinor(),
                currency: $order->currency(),
                createdAt: $order->createdAt(),
                updatedAt: $order->updatedAt(),
            );
        }

        $existing->update(
            status: $order->status()->value,
            amountMinor: $order->amountMinor(),
            currency: $order->currency(),
            updatedAt: $order->updatedAt(),
        );

        return $existing;
    }
}
