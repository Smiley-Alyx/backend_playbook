<?php

declare(strict_types=1);

namespace App\Domain\Order;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final readonly class OrderId
{
    private function __construct(public UuidInterface $value)
    {
    }

    public static function new(): self
    {
        return new self(Uuid::uuid7());
    }

    public static function fromString(string $value): self
    {
        return new self(Uuid::fromString($value));
    }

    public function toString(): string
    {
        return $this->value->toString();
    }
}
