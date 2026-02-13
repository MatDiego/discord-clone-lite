<?php

namespace App\Entity;

use App\Repository\ServerMemberRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ServerMemberRepository::class)]
class ServerMember
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Server $server;

    #[ORM\Column(nullable: false)]
    private DateTimeImmutable $joinedAt;

    /**
     * @var Collection<int, MemberRole>
     */
    #[ORM\OneToMany(targetEntity: MemberRole::class, mappedBy: 'serverMember', cascade: ['persist'], orphanRemoval: true)]
    private Collection $memberRoles;

    public function __construct(Server $server, User $user)
    {
        $this->id = Uuid::v7();
        $this->server = $server;
        $this->user = $user;
        $this->joinedAt = new DateTimeImmutable();
        $this->memberRoles = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;

    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setServer(Server $server): void
    {
        $this->server = $server;
    }
    public function getJoinedAt(): DateTimeImmutable
    {
        return $this->joinedAt;
    }
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Collection<int, MemberRole>
     */
    public function getMemberRoles(): Collection
    {
        return $this->memberRoles;
    }

    public function addMemberRole(MemberRole $memberRole): static
    {
        if (!$this->memberRoles->contains($memberRole)) {
            $this->memberRoles->add($memberRole);
            $memberRole->setServerMember($this);
        }

        return $this;
    }

    public function removeMemberRole(MemberRole $memberRole): static
    {
        $this->memberRoles->removeElement($memberRole);

        return $this;
    }
}
