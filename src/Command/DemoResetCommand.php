<?php

declare(strict_types=1);

namespace App\Command;

use Override;
use Psr\Log\LoggerInterface;
use Redis;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:demo:reset',
    description: 'Resets the demo application: reloads fixtures, flushes Redis sessions, sets next reset timer.',
)]
final class DemoResetCommand extends Command
{
    private const string NEXT_RESET_KEY = 'demo:next_reset';

    public function __construct(
        private readonly Redis $redis,
        private readonly LoggerInterface $logger,
        private readonly int $sessionLifetime,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Demo Reset');

        $this->flushRedisSessions($io);
        $this->reloadFixtures($io, $output);
        $this->scheduleNextReset($io);

        $io->success('Demo reset completed.');
        $this->logger->info('Demo reset completed successfully.');

        return Command::SUCCESS;
    }

    private function flushRedisSessions(SymfonyStyle $io): void
    {
        $io->section('Flushing Redis sessions...');

        $prefixes = ['sf_sess:', 'session_auth:', 'user_session:'];
        $totalDeleted = 0;

        foreach ($prefixes as $prefix) {
            $iterator = null;
            while ($keys = $this->redis->scan($iterator, $prefix . '*', 100)) {
                $this->redis->del($keys);
                $totalDeleted += count($keys);
            }
        }

        $io->text(sprintf('Deleted %d Redis keys.', $totalDeleted));
    }

    private function reloadFixtures(SymfonyStyle $io, OutputInterface $output): void
    {
        $io->section('Reloading database fixtures...');

        $command = $this->getApplication()?->find('doctrine:fixtures:load');

        if ($command === null) {
            $io->error('doctrine:fixtures:load command not found.');
            return;
        }

        $fixturesInput = new ArrayInput([
            '--no-interaction' => true,
            '--purge-with-truncate' => true,
        ]);

        $command->run($fixturesInput, $output);
    }

    private function scheduleNextReset(SymfonyStyle $io): void
    {
        $nextReset = time() + $this->sessionLifetime;
        $this->redis->set(self::NEXT_RESET_KEY, (string) $nextReset);

        $io->text(sprintf('Next reset scheduled at %s', date('Y-m-d H:i:s', $nextReset)));
    }
}
