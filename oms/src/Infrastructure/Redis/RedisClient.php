<?php

declare(strict_types=1);

namespace App\Infrastructure\Redis;

interface RedisClient
{
    public function get(string $key): string|false;

    public function set(string $key, string $value, int $ttlSeconds): void;

    public function setNxEx(string $key, string $value, int $ttlSeconds): bool;

    public function del(string $key): int;

    public function incr(string $key): int;

    public function expire(string $key, int $ttlSeconds): void;
}
