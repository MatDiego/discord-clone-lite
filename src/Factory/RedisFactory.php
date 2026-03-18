<?php

declare(strict_types=1);

namespace App\Factory;

use Redis;

final class RedisFactory
{
    public static function create(string $dsn): \Redis
    {
        $parsed = parse_url($dsn);

        $redis = new Redis();
        $redis->connect(
            $parsed['host'] ?? '127.0.0.1',
            $parsed['port'] ?? 6379,
        );

        return $redis;
    }
}
