<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\FriendInvitation;
use App\Entity\User;
use App\Enum\InvitationStatus;
use App\Repository\FriendInvitationRepository;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class FriendInvitationService
{
    public function __construct(
        private FriendInvitationRepository $invitationRepository,
        private UserRepository $userRepository,
        private NotificationService $notificationService,
        private LoggerInterface $logger,
    ) {
    }

    public function sendByUsername(User $sender, string $username): void
    {
        if ($username === '') {
            throw new UnprocessableEntityHttpException('Podaj nazwę użytkownika.');
        }

        $recipient = $this->userRepository->findByUsername($username);

        if ($recipient === null) {
            throw new NotFoundHttpException(sprintf('Użytkownik "%s" nie istnieje.', $username));
        }

        $this->send($sender, $recipient);
    }

    public function send(User $sender, User $recipient): void
    {
        if ($sender->getId()->equals($recipient->getId())) {
            throw new UnprocessableEntityHttpException('Nie możesz zaprosić samego siebie.');
        }

        if ($this->invitationRepository->areFriends($sender, $recipient)) {
            throw new ConflictHttpException('Jesteście już znajomymi.');
        }

        if ($this->invitationRepository->findPendingBetween($sender, $recipient) !== null) {
            throw new ConflictHttpException('Zaproszenie do znajomych już istnieje.');
        }

        $invitation = new FriendInvitation($sender, $recipient);
        $this->invitationRepository->add($invitation);
        $this->invitationRepository->flush();

        $this->notificationService->createFriendInvitationNotification($invitation);
    }

    public function accept(FriendInvitation $invitation, User $currentUser): void
    {
        $this->assertRecipient($invitation, $currentUser);
        $this->assertPending($invitation);

        $invitation->setStatus(InvitationStatus::ACCEPTED);
        $this->invitationRepository->flush();

        $this->notificationService->markReadByFriendInvitation($invitation);
        $this->notificationService->publishBadgeUpdateForUser($invitation->getRecipient());
        $this->notificationService->createFriendAcceptedNotification($invitation);
        try {
            $this->notificationService->publishFriendListUpdate($invitation->getSender());
            $this->notificationService->publishFriendListUpdate($invitation->getRecipient());
        } catch (\Throwable $e) {
            $this->logger->error('Mercure friend list publish failed: {msg}', ['msg' => $e->getMessage(), 'exception' => $e]);
        }
    }

    public function decline(FriendInvitation $invitation, User $currentUser): void
    {
        $this->assertRecipient($invitation, $currentUser);
        $this->assertPending($invitation);

        $invitation->setStatus(InvitationStatus::DECLINED);
        $this->invitationRepository->flush();

        $this->notificationService->markReadByFriendInvitation($invitation);
    }

    public function remove(FriendInvitation $invitation, User $currentUser): void
    {
        $isSender = $invitation->getSender()->getId()->equals($currentUser->getId());
        $isRecipient = $invitation->getRecipient()->getId()->equals($currentUser->getId());

        if (!$isSender && !$isRecipient) {
            throw new UnprocessableEntityHttpException('Brak dostępu do tej znajomości.');
        }

        if ($invitation->isPending()) {
            throw new UnprocessableEntityHttpException('To zaproszenie jest jeszcze oczekujące.');
        }

        $sender = $invitation->getSender();
        $recipient = $invitation->getRecipient();

        $this->invitationRepository->remove($invitation);
        $this->invitationRepository->flush();

        try {
            $this->notificationService->publishFriendListUpdate($sender);
            $this->notificationService->publishFriendListUpdate($recipient);
        } catch (\Throwable $e) {
            $this->logger->error('Mercure friend list publish failed: {msg}', ['msg' => $e->getMessage(), 'exception' => $e]);
        }
    }

    public function areFriends(User $a, User $b): bool
    {
        return $this->invitationRepository->areFriends($a, $b);
    }

    public function findPendingBetween(User $a, User $b): ?FriendInvitation
    {
        return $this->invitationRepository->findPendingBetween($a, $b);
    }

    private function assertRecipient(FriendInvitation $invitation, User $user): void
    {
        if (!$invitation->getRecipient()->getId()->equals($user->getId())) {
            throw new UnprocessableEntityHttpException('Brak dostępu do tego zaproszenia.');
        }
    }

    private function assertPending(FriendInvitation $invitation): void
    {
        if (!$invitation->isPending()) {
            throw new ConflictHttpException('To zaproszenie zostało już rozpatrzone.');
        }
    }
}
