<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Server;
use App\Entity\ServerInvitation;
use App\Entity\User;
use App\Enum\InvitationStatus;
use App\Repository\ServerInvitationRepository;
use App\Repository\ServerMemberRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class ServerInvitationService
{
    public function __construct(
        private ServerInvitationRepository $invitationRepository,
        private ServerMemberRepository $memberRepository,
        private ServerMemberService $serverMemberService,
        private NotificationService $notificationService,
        private MercureNotificationPublisher $publisher,
    ) {
    }

    public function send(Server $server, User $sender, User $recipient): void
    {
        if ($sender->getId()->equals($recipient->getId())) {
            throw new UnprocessableEntityHttpException('Nie możesz zaprosić samego siebie.');
        }

        if ($server->getOwner()->getId()->equals($recipient->getId())) {
            throw new UnprocessableEntityHttpException('Ten użytkownik jest właścicielem serwera.');
        }

        if ($this->memberRepository->findByUserAndServer($recipient, $server) !== null) {
            throw new ConflictHttpException('Ten użytkownik jest już członkiem serwera.');
        }

        if ($this->invitationRepository->findPendingForServerAndRecipient($server, $recipient) !== null) {
            throw new ConflictHttpException('Zaproszenie dla tego użytkownika już istnieje.');
        }

        $activeBan = $this->serverMemberService->getActiveBan($recipient, $server);
        if ($activeBan !== null) {
            throw new ConflictHttpException('Ten użytkownik jest zbanowany na tym serwerze.');
        }

        $invitation = new ServerInvitation($server, $sender, $recipient);
        $this->invitationRepository->add($invitation);
        $this->invitationRepository->flush();

        $this->notificationService->createInvitationNotification($invitation);
    }

    public function accept(ServerInvitation $invitation, User $currentUser): void
    {
        $this->assertRecipient($invitation, $currentUser);
        $this->assertPending($invitation);

        $activeBan = $this->serverMemberService->getActiveBan($invitation->getRecipient(), $invitation->getServer());
        if ($activeBan !== null) {
            throw new ConflictHttpException('Jesteś zbanowany na tym serwerze i nie możesz do niego dołączyć.');
        }

        $member = $this->serverMemberService->addMember($invitation->getServer(), $invitation->getRecipient());

        $invitation->setStatus(InvitationStatus::ACCEPTED);
        $this->invitationRepository->flush();

        $this->notificationService->markReadByInvitation($invitation);
        $this->notificationService->publishBadgeUpdateForUser($invitation->getRecipient());
        $this->notificationService->createInvitationAcceptedNotification($invitation);
        $this->publisher->publishMemberJoined($member->getServer());
    }

    public function decline(ServerInvitation $invitation, User $currentUser): void
    {
        $this->assertRecipient($invitation, $currentUser);
        $this->assertPending($invitation);

        $invitation->setStatus(InvitationStatus::DECLINED);
        $this->invitationRepository->flush();

        $this->notificationService->markReadByInvitation($invitation);
    }

    private function assertRecipient(ServerInvitation $invitation, User $user): void
    {
        if (!$invitation->getRecipient()->getId()->equals($user->getId())) {
            throw new UnprocessableEntityHttpException('Brak dostępu do tego zaproszenia.');
        }
    }

    private function assertPending(ServerInvitation $invitation): void
    {
        if (!$invitation->isPending()) {
            throw new ConflictHttpException('To zaproszenie zostało już rozpatrzone.');
        }
    }
}
