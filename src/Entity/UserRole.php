<?php

namespace App\Entity;

use App\Repository\UserRoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRoleRepository::class)]
class UserRole
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\Column(length: 30, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    private string $name;

    #[ORM\Column(nullable: false)]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $position;

    #[ORM\ManyToOne(inversedBy: 'userRoles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Server $server;

    /**
     * @var Collection<int, RolePermission>
     */
    #[ORM\OneToMany(targetEntity: RolePermission::class, mappedBy: 'role', cascade: ['persist'], orphanRemoval: true)]
    private Collection $rolePermissions;

    public function __construct(string $name, int $position, Server $server)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->position = $position;
        $this->server = $server;
        $this->rolePermissions = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $Position): static
    {
        $this->position = $Position;

        return $this;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setServer(Server $server): static
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return Collection<int, RolePermission>
     */
    public function getRolePermissions(): Collection
    {
        return $this->rolePermissions;
    }

    public function addRolePermission(RolePermission $rolePermission): static
    {
        if (!$this->rolePermissions->contains($rolePermission)) {
            $this->rolePermissions->add($rolePermission);
        }
        return $this;
    }

    public function removeRolePermission(RolePermission $rolePermission): static
    {
        $this->rolePermissions->removeElement($rolePermission);
        return $this;
    }
}
