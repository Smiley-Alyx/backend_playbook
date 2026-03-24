<?php

declare(strict_types=1);

namespace App\Application\UseCase\ConfirmPayment;

use App\Application\Contract\OrderRepository;
use App\Application\Contract\TransactionManager;
use App\Application\Exception\OrderNotFound;
use App\Application\Mapper\OrderToViewMapper;
use App\Domain\Order\OrderId;

final readonly class ConfirmPayment
{
    public function __construct(
        private OrderRepository $orders,
        private TransactionManager $tx,
        private OrderToViewMapper $mapper,
    ) {
    }

    public function execute(ConfirmPaymentRequest $request): ConfirmPaymentResponse
    {
        $result = $this->tx->transactional(function () use ($request): ConfirmPaymentResponse {
            $id = OrderId::fromString($request->orderId);
            $order = $this->orders->get($id);

            if ($order === null) {
                throw new OrderNotFound($id);
            }

            $order->confirmPayment($request->now);
            $this->orders->save($order);

            return new ConfirmPaymentResponse($this->mapper->map($order));
        });

        if (!$result instanceof ConfirmPaymentResponse) {
            throw new \RuntimeException('Transaction returned invalid result');
        }

        return $result;
    }
}
