<?php

declare(strict_types=1);

namespace App\Session;

use Override;
use Redis;
use SensitiveParameter;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\AbstractSessionHandler;

/**
 * Redis session handler with a fixed lifetime from creation.
 *
 * Unlike the default RedisSessionHandler, this handler does NOT renew
 * the TTL on every request. The session expires at a fixed time after
 * it was first created, regardless of user activity.
 */
final class FixedLifetimeRedisSessionHandler extends AbstractSessionHandler
{
    private readonly string $prefix;
    private readonly int $ttl;

    /**
     * The storage TTL is intentionally longer than SESSION_LIFETIME.
     * This ensures that sessions used for CSRF tokens (e.g. login page)
     * survive long enough for form submission, while the "logical" session
     * expiry is handled by the session_auth: key and SessionMonitorCommand.
     */
    public function __construct(
        private readonly Redis $redis,
        array $options = [],
    ) {
        $this->prefix = $options['prefix'] ?? 'sf_sess:';
        $this->ttl = max((int) ($options['ttl'] ?? 600) * 3, 300);
    }

    #[Override]
    protected function doRead(#[SensitiveParameter] string $sessionId): string
    {
        $data = $this->redis->get($this->prefix . $sessionId);

        return is_string($data) ? $data : '';
    }

    #[Override]
    protected function doWrite(#[SensitiveParameter] string $sessionId, string $data): bool
    {
        $key = $this->prefix . $sessionId;
        $remainingTtl = $this->redis->ttl($key);

        if (is_int($remainingTtl) && $remainingTtl > 0) {
            return (bool) $this->redis->setex($key, $remainingTtl, $data);
        }

        return (bool) $this->redis->setex($key, $this->ttl, $data);
    }

    #[Override]
    protected function doDestroy(#[SensitiveParameter] string $sessionId): bool
    {
        $this->redis->del($this->prefix . $sessionId);

        return true;
    }

    #[Override]
    public function close(): bool
    {
        return true;
    }

    #[Override]
    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    #[Override]
    public function updateTimestamp(#[SensitiveParameter] string $id, string $data): bool
    {
        return true;
    }
}
