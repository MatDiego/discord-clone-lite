<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Override;
use Redis;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Maintains Redis keys that track active authenticated sessions.
 *
 * Keys created:
 *  - session_auth:{sessionId}:{userId} TTL = SESSION_LIFETIME
 *    -> expires naturally and triggers SessionMonitorCommand via keyspace notification
 *  - user_session:{userId} TTL = SESSION_LIFETIME + buffer
 *    -> reverse lookup used by SingleSessionSubscriber to find old session
 */
final readonly class SessionTrackingSubscriber implements EventSubscriberInterface
{
    private const string AUTH_KEY_PREFIX = 'session_auth:';
    private const string USER_SESSION_PREFIX = 'user_session:';
    private const int MAPPING_TTL_BUFFER = 30;

    public function __construct(
        private Redis $redis,
        private TokenStorageInterface $tokenStorage,
        private int $sessionLifetime,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof User) {
            return;
        }

        $sessionId = $event->getRequest()->getSession()->getId();
        if ($sessionId === '') {
            return;
        }

        $userId = $user->getId()->toRfc4122();
        $authKey = self::AUTH_KEY_PREFIX . $sessionId . ':' . $userId;

        if ($this->redis->exists($authKey) > 0) {
            return;
        }

        $this->redis->setex($authKey, $this->sessionLifetime, '1');
        $this->redis->setex(self::USER_SESSION_PREFIX . $userId, $this->sessionLifetime + self::MAPPING_TTL_BUFFER, $sessionId);
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -20],
        ];
    }
}
