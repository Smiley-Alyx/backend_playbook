<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

final readonly class ExtRedisClient implements RedisClient
{
    public function __construct(
        private \Redis $redis,
    ) {
    }

    public function get(string $key): string|false
    {
        /** @var string|false $value */
        $value = $this->redis->get($key);
        if ($value === false) {
            return false;
        }

        return $value;
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $this->redis->set($key, $value, ['ex' => $ttlSeconds]);
    }

    public function setNxEx(string $key, string $value, int $ttlSeconds): bool
    {
        $result = $this->redis->set($key, $value, ['nx', 'ex' => $ttlSeconds]);

        return $result === true;
    }

    public function del(string $key): int
    {
        return (int) $this->redis->del($key);
    }

    public function incr(string $key): int
    {
        return (int) $this->redis->incr($key);
    }

    public function expire(string $key, int $ttlSeconds): void
    {
        $this->redis->expire($key, $ttlSeconds);
    }
}
