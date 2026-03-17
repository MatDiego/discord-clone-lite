<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InvitationStatus;
use App\Repository\FriendInvitationRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: FriendInvitationRepository::class)]
class FriendInvitation
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $sender;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $recipient;

    #[ORM\Column(type: 'string', length: 20, enumType: InvitationStatus::class)]
    private InvitationStatus $status;

    #[ORM\Column(nullable: false)]
    private DateTimeImmutable $createdAt;

    public function __construct(User $sender, User $recipient)
    {
        $this->id = Uuid::v7();
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->status = InvitationStatus::PENDING;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getSender(): User
    {
        return $this->sender;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function getStatus(): InvitationStatus
    {
        return $this->status;
    }

    public function setStatus(InvitationStatus $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING;
    }

    public function getSenderName(): string
    {
        return $this->sender->getUsername();
    }

    public function getRecipientName(): string
    {
        return $this->recipient->getUsername();
    }
}
