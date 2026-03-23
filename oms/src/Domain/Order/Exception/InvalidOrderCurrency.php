<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

final class InvalidOrderCurrency extends OrderDomainException
{
    public function __construct(public readonly string $currency)
    {
        parent::__construct(sprintf('Invalid order currency: %s', $currency));
    }
}
