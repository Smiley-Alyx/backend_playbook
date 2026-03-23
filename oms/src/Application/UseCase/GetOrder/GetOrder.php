<?php

declare(strict_types=1);

namespace App\Application\UseCase\GetOrder;

use App\Application\Contract\OrderQueryRepository;
use App\Application\Exception\OrderNotFound;
use App\Domain\Order\OrderId;

final readonly class GetOrder
{
    public function __construct(private OrderQueryRepository $orders)
    {
    }

    public function execute(GetOrderRequest $request): GetOrderResponse
    {
        $id = OrderId::fromString($request->orderId);
        $order = $this->orders->get($id);

        if ($order === null) {
            throw new OrderNotFound($id);
        }

        return new GetOrderResponse($order);
    }
}
