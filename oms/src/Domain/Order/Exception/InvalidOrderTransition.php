<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\OrderStatus;

final class InvalidOrderTransition extends OrderDomainException
{
    public function __construct(
        public readonly OrderStatus $from,
        public readonly OrderStatus $to,
    ) {
        parent::__construct(sprintf('Invalid order transition: %s -> %s', $from->value, $to->value));
    }
}
