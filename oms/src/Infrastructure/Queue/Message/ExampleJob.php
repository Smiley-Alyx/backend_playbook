<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Message;

final readonly class ExampleJob
{
    public function __construct(
        public string $message,
        public int $attempt = 0,
    ) {
    }
}
