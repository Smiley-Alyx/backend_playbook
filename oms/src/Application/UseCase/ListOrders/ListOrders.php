<?php

declare(strict_types=1);

namespace App\Application\UseCase\ListOrders;

use App\Application\Contract\OrderQueryRepository;
use App\Application\DTO\OrderListQuery;

final readonly class ListOrders
{
    public function __construct(private OrderQueryRepository $orders)
    {
    }

    public function execute(ListOrdersRequest $request): ListOrdersResponse
    {
        $query = new OrderListQuery(
            page: $request->page,
            perPage: $request->perPage,
            status: $request->status,
        );

        return new ListOrdersResponse($this->orders->list($query));
    }
}
