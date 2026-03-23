<?php

declare(strict_types=1);

namespace App\Domain\Order;

use Symfony\Component\Uid\Uuid;

final readonly class OrderId
{
    private function __construct(public Uuid $value)
    {
    }

    public static function new(): self
    {
        return new self(Uuid::v7());
    }

    public static function fromString(string $value): self
    {
        return new self(Uuid::fromString($value));
    }

    public function toString(): string
    {
        return $this->value->toRfc4122();
    }
}
