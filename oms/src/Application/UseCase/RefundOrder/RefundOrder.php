<?php

declare(strict_types=1);

namespace App\Application\UseCase\RefundOrder;

use App\Application\Contract\OrderRepository;
use App\Application\Contract\TransactionManager;
use App\Application\Exception\OrderNotFound;
use App\Application\Mapper\OrderToViewMapper;
use App\Domain\Order\OrderId;

final readonly class RefundOrder
{
    public function __construct(
        private OrderRepository $orders,
        private TransactionManager $tx,
        private OrderToViewMapper $mapper,
    ) {
    }

    public function execute(RefundOrderRequest $request): RefundOrderResponse
    {
        return $this->tx->transactional(function () use ($request): RefundOrderResponse {
            $id = OrderId::fromString($request->orderId);
            $order = $this->orders->get($id);

            if ($order === null) {
                throw new OrderNotFound($id);
            }

            $order->refund($request->now);
            $this->orders->save($order);

            return new RefundOrderResponse($this->mapper->map($order));
        });
    }
}
