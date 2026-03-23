<?php

declare(strict_types=1);

namespace App\Application\UseCase\ConfirmPayment;

use App\Application\DTO\OrderView;

final readonly class ConfirmPaymentResponse
{
    public function __construct(public OrderView $order)
    {
    }
}
