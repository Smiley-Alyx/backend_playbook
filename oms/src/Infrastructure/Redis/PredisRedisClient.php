<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

use Predis\ClientInterface;

/**
 * @internal
 */
final readonly class PredisRedisClient implements RedisClient
{
    public function __construct(
        private ClientInterface $client,
    ) {
    }

    public function get(string $key): string|false
    {
        $value = $this->client->get($key);
        if ($value === null) {
            return false;
        }

        return (string) $value;
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $this->client->set($key, $value, 'EX', $ttlSeconds);
    }

    public function setNxEx(string $key, string $value, int $ttlSeconds): bool
    {
        $result = $this->client->set($key, $value, 'EX', $ttlSeconds, 'NX');

        return $result !== null;
    }

    public function del(string $key): int
    {
        $result = $this->client->del([$key]);

        return (int) $result;
    }

    public function incr(string $key): int
    {
        $result = $this->client->incr($key);

        return (int) $result;
    }

    public function expire(string $key, int $ttlSeconds): void
    {
        $this->client->expire($key, $ttlSeconds);
    }
}
