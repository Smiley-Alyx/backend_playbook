<?php

declare(strict_types=1);

namespace App\Application\UseCase\CreateOrder;

use App\Application\Contract\OrderRepository;
use App\Application\Contract\TransactionManager;
use App\Application\Mapper\OrderToViewMapper;
use App\Domain\Order\Order;
use App\Domain\Order\OrderId;

final readonly class CreateOrder
{
    public function __construct(
        private OrderRepository $orders,
        private TransactionManager $tx,
        private OrderToViewMapper $mapper,
    ) {
    }

    public function execute(CreateOrderRequest $request): CreateOrderResponse
    {
        $result = $this->tx->transactional(function () use ($request): CreateOrderResponse {
            $id = OrderId::new();

            $order = Order::create(
                id: $id,
                amount: $request->amountMinor,
                currency: $request->currency,
                now: $request->now,
            );

            $this->orders->save($order);

            return new CreateOrderResponse($this->mapper->map($order));
        });

        if (!$result instanceof CreateOrderResponse) {
            throw new \RuntimeException('Transaction returned invalid result');
        }

        return $result;
    }
}
