<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ServerBanRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ServerBanRepository::class)]
class ServerBan
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Server $server;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $bannedBy;

    #[ORM\Column(nullable: false)]
    private DateTimeImmutable $bannedAt;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $expiresAt;

    public function __construct(User $user, Server $server, User $bannedBy, ?DateTimeImmutable $expiresAt = null)
    {
        $this->id = Uuid::v7();
        $this->user = $user;
        $this->server = $server;
        $this->bannedBy = $bannedBy;
        $this->bannedAt = new DateTimeImmutable();
        $this->expiresAt = $expiresAt;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getBannedBy(): User
    {
        return $this->bannedBy;
    }

    public function getBannedAt(): DateTimeImmutable
    {
        return $this->bannedAt;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isPermanent(): bool
    {
        return $this->expiresAt === null;
    }

    public function isActive(): bool
    {
        if ($this->isPermanent()) {
            return true;
        }

        return $this->expiresAt > new DateTimeImmutable();
    }
}
