<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:users:cleanup',
    description: 'Deletes unverified users older than a specific time (based on UUID v7).',
)]
class CleanupUnverifiedUsersCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('max-age', null, InputOption::VALUE_OPTIONAL,
                'Max age of the account in minutes (e.g. 60)', '60')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $minutes = $input->getOption('max-age');

        try {
            $thresholdDate = new \DateTimeImmutable(sprintf('-%d minutes', $minutes));
            $cutoffUuid = $this->generateCutoffUuidV7($thresholdDate);

        } catch (\Exception $e) {
            $io->error('Error calculating date/UUID: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->title('Unverified Users Cleanup');
        $io->text(sprintf(
            'Removing unverified accounts created before: %s (older than %d minutes).',
            $thresholdDate->format('Y-m-d H:i:s'),
            $minutes
        ));

        $deletedCount = $this->userRepository->deleteUnverifiedUsersOlderThanId($cutoffUuid);

        if ($deletedCount > 0) {
            $io->success(sprintf('Done. Removed %d unverified user(s).', $deletedCount));
        } else {
            $io->info('No accounts found matching the criteria.');
        }

        return Command::SUCCESS;
    }

    /**
     * Creates the smallest possible UUID v7 for a given date.
     * Any UUID generated after this date will be mathematically "greater".
     * Any UUID generated before this date will be mathematically "smaller".
     */
    private function generateCutoffUuidV7(\DateTimeInterface $date): Uuid
    {
        $timestampMs = (int) $date->format('Uv');
        $hexTs = str_pad(dechex($timestampMs), 12, '0', STR_PAD_LEFT);

        $uuidString = sprintf(
            '%s-%s-7000-8000-000000000000',
            substr($hexTs, 0, 8),
            substr($hexTs, 8, 4)
        );

        return Uuid::fromString($uuidString);
    }
}
