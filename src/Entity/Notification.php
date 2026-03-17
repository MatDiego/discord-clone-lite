<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\NotificationType;
use App\Repository\NotificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\Column(type: 'string', length: 50, enumType: NotificationType::class)]
    private NotificationType $type;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ServerInvitation $invitation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FriendInvitation $friendInvitation = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $serverName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $actorName = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column(nullable: false)]
    private DateTimeImmutable $createdAt;

    public function __construct(User $recipient, NotificationType $type)
    {
        $this->id = Uuid::v7();
        $this->recipient = $recipient;
        $this->type = $type;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function getType(): NotificationType
    {
        return $this->type;
    }

    public function getInvitation(): ?ServerInvitation
    {
        return $this->invitation;
    }

    public function setInvitation(?ServerInvitation $invitation): void
    {
        $this->invitation = $invitation;
    }

    public function getFriendInvitation(): ?FriendInvitation
    {
        return $this->friendInvitation;
    }

    public function setFriendInvitation(?FriendInvitation $friendInvitation): void
    {
        $this->friendInvitation = $friendInvitation;
    }

    public function getServerName(): ?string
    {
        return $this->serverName;
    }

    public function setServerName(?string $serverName): void
    {
        $this->serverName = $serverName;
    }

    public function getActorName(): ?string
    {
        return $this->actorName;
    }

    public function setActorName(?string $actorName): void
    {
        $this->actorName = $actorName;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function markAsRead(): void
    {
        $this->isRead = true;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isServerDeleted(): bool
    {
        return $this->type === NotificationType::SERVER_INVITATION && null === $this->invitation;
    }

    public function isInvitationPending(): bool
    {
        if ($this->type === NotificationType::SERVER_INVITATION) {
            return $this->invitation !== null && $this->invitation->isPending();
        }

        if ($this->type === NotificationType::FRIEND_INVITATION) {
            return $this->friendInvitation !== null && $this->friendInvitation->isPending();
        }

        return false;
    }

    public function needsOkButton(): bool
    {
        return $this->isServerDeleted() || in_array($this->type, [
            NotificationType::INVITATION_ACCEPTED,
            NotificationType::KICKED_FROM_SERVER,
            NotificationType::BANNED_FROM_SERVER,
            NotificationType::FRIEND_INVITATION_ACCEPTED,
        ]);
    }

    public function getRelatedServerName(): string
    {
        return $this->invitation?->getServerName() ?? $this->serverName ?? '(nieznany)';
    }

    public function getUserName(string $fallback = '-'): string
    {
        if ($this->type === NotificationType::INVITATION_ACCEPTED) {
            return $this->invitation?->getRecipientName() ?? $this->actorName ?? $fallback;
        }

        if ($this->type === NotificationType::FRIEND_INVITATION) {
            return $this->friendInvitation?->getSenderName() ?? $this->actorName ?? $fallback;
        }

        if ($this->type === NotificationType::FRIEND_INVITATION_ACCEPTED) {
            return $this->friendInvitation?->getRecipientName() ?? $this->actorName ?? $fallback;
        }

        return $this->invitation?->getSenderName() ?? $this->actorName ?? $fallback;
    }
}
