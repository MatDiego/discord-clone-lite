<?php

namespace App\Entity;

use App\Repository\ChannelOverrideRepository;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ChannelOverrideRepository::class)]
class ChannelOverride
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Channel $channel;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?UserRole $role;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?ServerMember $serverMember;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Permission $permission;

    #[ORM\Column]
    private bool $allow;

    public function __construct(Channel $channel, ?UserRole $role, ?ServerMember $serverMember, Permission $permission)
    {
        if (null === $role && null === $serverMember) {
            throw new InvalidArgumentException('Musisz podać rolę LUB członka.');
        }
        $this->id = Uuid::v7();
        $this->channel = $channel;
        $this->role = $role;
        $this->serverMember = $serverMember;
        $this->permission = $permission;
        $this->allow = false;
    }


    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getChannel(): Channel
    {
        return $this->channel;
    }

    public function setChannel(Channel $channel): static
    {
        $this->channel = $channel;

        return $this;
    }

    public function getRole(): ?UserRole
    {
        return $this->role;
    }

    public function setRole(?UserRole $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getServerMember(): ?ServerMember
    {
        return $this->serverMember;
    }

    public function setServerMember(?ServerMember $serverMember): static
    {
        $this->serverMember = $serverMember;

        return $this;
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }

    public function setPermission(Permission $permission): static
    {
        $this->permission = $permission;
        return $this;
    }

    public function isAllow(): bool
    {
        return $this->allow;
    }

    public function setAllow(bool $allow): static
    {
        $this->allow = $allow;
        return $this;
    }
}
