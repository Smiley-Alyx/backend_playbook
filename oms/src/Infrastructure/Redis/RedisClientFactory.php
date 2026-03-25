<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Predis\ClientInterface;

final class RedisClientFactory
{
    public function create(object $connection): RedisClient
    {
        if (\is_a($connection, 'Redis')) {
            /** @var \Redis $connection */
            return new ExtRedisClient($connection);
        }

        if ($connection instanceof ClientInterface) {
            return new PredisRedisClient($connection);
        }

        throw new \InvalidArgumentException('Unsupported Redis connection type: ' . $connection::class);
    }
}
