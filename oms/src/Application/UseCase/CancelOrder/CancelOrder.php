<?php

declare(strict_types=1);

namespace App\Application\UseCase\CancelOrder;

use App\Application\Contract\OrderRepository;
use App\Application\Contract\TransactionManager;
use App\Application\Exception\OrderNotFound;
use App\Application\Mapper\OrderToViewMapper;
use App\Domain\Order\OrderId;

final readonly class CancelOrder
{
    public function __construct(
        private OrderRepository $orders,
        private TransactionManager $tx,
        private OrderToViewMapper $mapper,
    ) {
    }

    public function execute(CancelOrderRequest $request): CancelOrderResponse
    {
        return $this->tx->transactional(function () use ($request): CancelOrderResponse {
            $id = OrderId::fromString($request->orderId);
            $order = $this->orders->get($id);

            if ($order === null) {
                throw new OrderNotFound($id);
            }

            $order->cancel($request->now);
            $this->orders->save($order);

            return new CancelOrderResponse($this->mapper->map($order));
        });
    }
}
