<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\MemberRole;
use App\Entity\Server;
use App\Entity\ServerBan;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Repository\MemberRoleRepository;
use App\Repository\ServerBanRepository;
use App\Repository\ServerMemberRepository;
use App\Repository\UserRoleRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class ServerMemberService
{
    public function __construct(
        private ServerMemberRepository $memberRepository,
        private UserRoleRepository $userRoleRepository,
        private MemberRoleRepository $memberRoleRepository,
        private ServerBanRepository $banRepository,
        private NotificationService $notificationService,
        private MercureNotificationPublisher $mercurePublisher,
        private LoggerInterface $logger,
    ) {
    }

    public function addMember(Server $server, User $user): ServerMember
    {
        $member = new ServerMember($server, $user);
        $this->memberRepository->add($member);

        $defaultRole = $this->userRoleRepository->findDefaultRoleForServer($server);
        if ($defaultRole !== null) {
            $this->memberRoleRepository->add(new MemberRole($member, $defaultRole));
        }

        return $member;
    }

    public function kick(ServerMember $member): void
    {
        $server = $member->getServer();
        $targetUser = $member->getUser();

        $this->memberRepository->remove($member);
        $this->memberRepository->flush();

        $this->notificationService->createKickedNotification($targetUser, $server);

        $this->publishMemberListUpdate($server);
        $this->publishRedirectIfOnServer($targetUser);
    }

    public function ban(ServerMember $member, ?string $duration, User $bannedBy): void
    {
        $server = $member->getServer();
        $targetUser = $member->getUser();

        $existingBan = $this->banRepository->findActiveBan($targetUser, $server);
        if ($existingBan !== null) {
            throw new ConflictHttpException('Ten użytkownik jest już zbanowany.');
        }

        $expiresAt = $this->resolveExpiry($duration);

        $ban = new ServerBan($targetUser, $server, $bannedBy, $expiresAt);
        $this->banRepository->add($ban);

        $this->memberRepository->remove($member);
        $this->memberRepository->flush();

        $this->notificationService->createBannedNotification($targetUser, $server, $duration);

        $this->publishMemberListUpdate($server);
        $this->publishRedirectIfOnServer($targetUser);
    }

    public function getActiveBan(User $user, Server $server): ?ServerBan
    {
        return $this->banRepository->findActiveBan($user, $server);
    }

    private function resolveExpiry(?string $duration): ?DateTimeImmutable
    {
        return match ($duration) {
            'day' => new DateTimeImmutable('+1 day'),
            'week' => new DateTimeImmutable('+1 week'),
            'month' => new DateTimeImmutable('+1 month'),
            default => null,
        };
    }

    private function publishMemberListUpdate(Server $server): void
    {
        try {
            $this->mercurePublisher->publishMemberJoined($server);
        } catch (\Throwable $e) {
            $this->logger->error('Mercure member list publish failed: {msg}', ['msg' => $e->getMessage(), 'exception' => $e]);
        }
    }

    private function publishRedirectIfOnServer(User $user): void
    {
        try {
            $this->mercurePublisher->publishRedirect($user);
        } catch (\Throwable $e) {
            $this->logger->error('Mercure redirect publish failed: {msg}', ['msg' => $e->getMessage(), 'exception' => $e]);
        }
    }
}
