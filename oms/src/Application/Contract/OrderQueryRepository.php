<?php

declare(strict_types=1);

namespace App\Application\Contract;

use App\Application\DTO\OrderListResult;
use App\Application\DTO\OrderListQuery;
use App\Application\DTO\OrderView;
use App\Domain\Order\OrderId;

interface OrderQueryRepository
{
    public function get(OrderId $id): ?OrderView;

    public function list(OrderListQuery $query): OrderListResult;
}
