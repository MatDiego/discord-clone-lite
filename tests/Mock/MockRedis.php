<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Redis;

final class MockRedis extends Redis
{
    /** @var array<string, string> */
    private array $store = [];

    public function __construct()
    {
    }

    public function connect(
        string $host,
        int $port = 6379,
        float|int $timeout = 0,
        ?string $persistent_id = null,
        int $retry_interval = 0,
        float|int $read_timeout = 0,
        ?array $context = null,
    ): bool {
        return true;
    }

    public function get(string $key): string|false
    {
        return $this->store[$key] ?? false;
    }

    public function set(string $key, mixed $value, mixed $options = null): string|bool
    {
        $this->store[$key] = (string) $value;

        return true;
    }

    public function setex(string $key, int $expire, mixed $value): bool
    {
        $this->store[$key] = (string) $value;

        return true;
    }

    public function del(string|array $key, string ...$other_keys): int|false
    {
        $keys = is_array($key) ? $key : [$key, ...$other_keys];
        $deleted = 0;
        foreach ($keys as $k) {
            if (isset($this->store[$k])) {
                unset($this->store[$k]);
                ++$deleted;
            }
        }

        return $deleted;
    }

    public function exists(mixed $key, mixed ...$other_keys): int|bool
    {
        return isset($this->store[$key]) ? 1 : 0;
    }

    public function scan(string|int|null &$iterator, ?string $pattern = null, int $count = 0, ?string $type = null): array|false
    {
        $iterator = 0;

        return false;
    }

    public function ttl(string $key): int|false
    {
        return isset($this->store[$key]) ? -1 : -2;
    }

    public function getHost(): string
    {
        return '127.0.0.1';
    }

    public function getPort(): int
    {
        return 6379;
    }

    public function subscribe(array $channels, callable $callback): bool
    {
        return true;
    }
}
