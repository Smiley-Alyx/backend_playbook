<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Domain\Order\Order;
use App\Domain\Order\OrderId;

interface OrderRepository
{
    public function get(OrderId $id): ?Order;

    public function save(Order $order): void;
}
