<?php

namespace App\Entity;

use App\Repository\ServerMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ServerMemberRepository::class)]
class ServerMember
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Server $server = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->joinedAt = new \DateTimeImmutable();
    }


    public function getId(): ?Uuid
    {
        return $this->id;

    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getServer(): ?Server
    {
        return $this->server;
    }

    public function setServer(?Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
