<?php

declare(strict_types=1);

namespace App\Domain\Order;

enum OrderStatus: string
{
    case Created = 'created';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Fulfilled = 'fulfilled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Created => in_array($next, [self::Paid, self::Cancelled], true),
            self::Paid => in_array($next, [self::Fulfilled, self::Refunded], true),
            self::Fulfilled => false,
            self::Cancelled => false,
            self::Refunded => false,
        };
    }
}
