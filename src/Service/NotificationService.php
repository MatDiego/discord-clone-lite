<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Server;
use App\Entity\ServerInvitation;
use App\Entity\User;
use App\Enum\NotificationType;
use App\Repository\NotificationRepository;

final readonly class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private MercureNotificationPublisher $publisher,
    ) {
    }

    public function createInvitationNotification(ServerInvitation $invitation): void
    {
        $notification = new Notification($invitation->getRecipient(), NotificationType::SERVER_INVITATION);
        $notification->setInvitation($invitation);
        $notification->setServerName($invitation->getServerName());
        $notification->setActorName($invitation->getSenderName());

        $this->persist($notification);
    }

    public function createInvitationAcceptedNotification(ServerInvitation $invitation): void
    {
        $notification = new Notification($invitation->getSender(), NotificationType::INVITATION_ACCEPTED);
        $notification->setInvitation($invitation);
        $notification->setServerName($invitation->getServerName());
        $notification->setActorName($invitation->getRecipientName());

        $this->persist($notification);
    }

    public function createKickedNotification(User $recipient, Server $server): void
    {
        $notification = new Notification($recipient, NotificationType::KICKED_FROM_SERVER);
        $notification->setServerName($server->getName());

        $this->persist($notification);
    }

    public function createBannedNotification(User $recipient, Server $server): void
    {
        $notification = new Notification($recipient, NotificationType::BANNED_FROM_SERVER);
        $notification->setServerName($server->getName());

        $this->persist($notification);
    }

    public function createServerDeletedNotification(User $recipient, string $serverName): void
    {
        $notification = new Notification($recipient, NotificationType::SERVER_DELETED);
        $notification->setServerName($serverName);

        $this->persist($notification);
    }

    public function publishMemberJoinedStream(Server $server): void
    {
        $this->publisher->publishMemberJoined($server);
    }

    public function publishBadgeUpdateForUser(User $user): void
    {
        $this->publisher->publishBadge($user, $this->notificationRepository->countUnread($user));
    }


    public function markAllAsRead(User $user): void
    {
        $this->notificationRepository->markAllAsReadForUser($user);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
        $this->notificationRepository->flush();
    }

    public function markReadByInvitation(ServerInvitation $invitation): void
    {
        $this->notificationRepository->markReadByInvitation($invitation);
    }

    public function getUnreadCount(User $user): int
    {
        return $this->notificationRepository->countUnread($user);
    }

    /**
     * @return Notification[]
     */
    public function getUnreadNotifications(User $user): array
    {
        return $this->notificationRepository->findUnreadForUser($user);
    }

    private function persist(Notification $notification): void
    {
        $recipient = $notification->getRecipient();

        $this->notificationRepository->add($notification);
        $this->notificationRepository->flush();

        $this->publisher->publishBadge($recipient, $this->notificationRepository->countUnread($recipient));
    }
}
