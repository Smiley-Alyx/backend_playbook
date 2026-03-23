<?php

declare(strict_types=1);

namespace App\Domain\Order;

use App\Domain\Order\Exception\InvalidOrderAmount;
use App\Domain\Order\Exception\InvalidOrderCurrency;
use App\Domain\Order\Exception\InvalidOrderTransition;
use DateTimeImmutable;

final class Order
{
    private OrderStatus $status;

    private function __construct(
        private readonly OrderId $id,
        private readonly int $amountMinor,
        private readonly string $currency,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        $this->status = OrderStatus::Created;

        $this->assertInvariants();
    }

    public static function create(OrderId $id, int $amount, string $currency, DateTimeImmutable $now): self
    {
        $normalizedCurrency = strtoupper(trim($currency));

        return new self(
            id: $id,
            amountMinor: $amount,
            currency: $normalizedCurrency,
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function amountMinor(): int
    {
        return $this->amountMinor;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function confirmPayment(DateTimeImmutable $now): void
    {
        $this->transitionTo(OrderStatus::Paid, $now);
    }

    public function cancel(DateTimeImmutable $now): void
    {
        $this->transitionTo(OrderStatus::Cancelled, $now);
    }

    public function refund(DateTimeImmutable $now): void
    {
        $this->transitionTo(OrderStatus::Refunded, $now);
    }

    public function fulfill(DateTimeImmutable $now): void
    {
        $this->transitionTo(OrderStatus::Fulfilled, $now);
    }

    private function transitionTo(OrderStatus $next, DateTimeImmutable $now): void
    {
        if (!$this->status->canTransitionTo($next)) {
            throw new InvalidOrderTransition($this->status, $next);
        }

        $this->status = $next;
        $this->updatedAt = $now;

        $this->assertInvariants();
    }

    private function assertInvariants(): void
    {
        if ($this->amountMinor <= 0) {
            throw new InvalidOrderAmount($this->amountMinor);
        }

        $currency = strtoupper(trim($this->currency));

        if ($currency !== $this->currency || !preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidOrderCurrency($this->currency);
        }
    }
}
