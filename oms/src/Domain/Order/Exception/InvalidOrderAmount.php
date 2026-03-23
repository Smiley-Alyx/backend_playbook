<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class InvalidOrderAmount extends OrderDomainException
{
    public function __construct(public readonly int $amountMinor)
    {
        parent::__construct(sprintf('Invalid order amount (minor units): %d', $amountMinor));
    }
}
