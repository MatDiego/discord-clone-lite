<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\MercureNotificationPublisher;
use Override;
use Psr\Log\LoggerInterface;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Listens for Redis keyspace notifications on session_auth: key expiry.
 *
 * When a session_auth:{sessionId}:{userId} key expires, it means the
 * user's logical session has ended. The command then:
 *  1. Publishes a Mercure redirect to the user's browser
 *  2. Cleans up the sf_sess: and user_session: keys
 */
#[AsCommand(
    name: 'app:session:monitor',
    description: 'Listens for Redis session expiry events and notifies users via Mercure.',
)]
final class SessionMonitorCommand extends Command
{
    private const string AUTH_KEY_PREFIX = 'session_auth:';
    private const string SESSION_PREFIX = 'sf_sess:';
    private const string USER_SESSION_PREFIX = 'user_session:';

    public function __construct(
        private readonly Redis $redis,
        private readonly MercureNotificationPublisher $publisher,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Session Monitor');
        $io->text('Listening for session_auth: expiry events...');

        $subscriber = new Redis();
        $subscriber->connect(
            $this->redis->getHost(),
            $this->redis->getPort(),
        );

        /** @psalm-suppress InvalidArgument, InvalidCast - phpredis stub types are incorrect for subscribe() */
        $subscriber->subscribe(['__keyevent@0__:expired'], function (Redis $redis, string $channel, string $expiredKey): void {
            if (!str_starts_with($expiredKey, self::AUTH_KEY_PREFIX)) {
                return;
            }

            $remainder = substr($expiredKey, strlen(self::AUTH_KEY_PREFIX));
            $separatorPos = strpos($remainder, ':');

            if ($separatorPos === false) {
                $this->logger->warning('Malformed session_auth key expired.', ['key' => $expiredKey]);
                return;
            }

            $sessionId = substr($remainder, 0, $separatorPos);
            $userId = substr($remainder, $separatorPos + 1);

            $this->redis->del(self::SESSION_PREFIX . $sessionId);
            $this->redis->del(self::USER_SESSION_PREFIX . $userId);

            try {
                $this->publisher->publishSessionExpired($userId);
                $this->logger->info('Session expired, user notified.', ['userId' => $userId, 'sessionId' => $sessionId]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to publish session expiry.', [
                    'userId' => $userId,
                    'sessionId' => $sessionId,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return Command::SUCCESS;
    }
}
