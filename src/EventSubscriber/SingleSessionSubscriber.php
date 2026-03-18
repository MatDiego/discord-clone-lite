<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\MercureNotificationPublisher;
use Override;
use Psr\Log\LoggerInterface;
use Redis;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Throwable;

/**
 * Enforces single active session per user.
 *
 * On every successful login, checks if the user already has an active session.
 * If so, the old session is destroyed and the previously logged-in browser
 * receives a Mercure redirect to the login page with a "kicked" message.
 */
final readonly class SingleSessionSubscriber implements EventSubscriberInterface
{
    private const string SESSION_PREFIX = 'sf_sess:';
    private const string AUTH_KEY_PREFIX = 'session_auth:';
    private const string USER_SESSION_PREFIX = 'user_session:';
    private const int MAPPING_TTL_BUFFER = 30;

    public function __construct(
        private Redis $redis,
        private MercureNotificationPublisher $publisher,
        private LoggerInterface $logger,
        private int $sessionLifetime,
    ) {
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $userId = $user->getId()->toRfc4122();
        $newSessionId = $event->getRequest()->getSession()->getId();

        $oldSessionId = $this->redis->get(self::USER_SESSION_PREFIX . $userId);

        if (\is_string($oldSessionId) && $oldSessionId !== $newSessionId) {
            $this->kickOldSession($userId, $oldSessionId);
        }

        $this->redis->setex(self::AUTH_KEY_PREFIX . $newSessionId . ':' . $userId, $this->sessionLifetime, '1');
        $this->redis->setex(self::USER_SESSION_PREFIX . $userId, $this->sessionLifetime + self::MAPPING_TTL_BUFFER, $newSessionId);
    }

    private function kickOldSession(string $userId, string $oldSessionId): void
    {
        try {
            $this->publisher->publishSessionKicked($userId);
        } catch (Throwable $e) {
            $this->logger->error('Failed to publish session kicked notification.', [
                'userId' => $userId,
                'error' => $e->getMessage(),
            ]);
        }

        $this->redis->del(self::SESSION_PREFIX . $oldSessionId);
        $this->redis->del(self::AUTH_KEY_PREFIX . $oldSessionId . ':' . $userId);

        $this->logger->info('Old session kicked.', [
            'userId' => $userId,
            'oldSessionId' => $oldSessionId,
        ]);
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}
