<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetOrder;

final readonly class GetOrderRequest
{
    public function __construct(public string $orderId)
    {
    }
}
