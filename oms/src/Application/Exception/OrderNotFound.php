<?php

declare(strict_types=1);

namespace App\Application\Exception;

use App\Domain\Order\OrderId;

final class OrderNotFound extends ApplicationException
{
    public function __construct(OrderId $id)
    {
        parent::__construct(sprintf('Order not found: %s', $id->toString()));
    }
}
