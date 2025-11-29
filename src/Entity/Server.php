<?php

namespace App\Entity;

use App\Repository\ServerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ServerRepository::class)]
class Server
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $id;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'ownedServers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, ServerMember>
     */
    #[ORM\OneToMany(targetEntity: ServerMember::class, mappedBy: 'server', orphanRemoval: true)]
    private Collection $members;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->members = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, ServerMember>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(ServerMember $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->setServer($this);
        }

        return $this;
    }

    public function removeMember(ServerMember $member): static
    {
        if ($this->members->removeElement($member)) {
            // set the owning side to null (unless already changed)
            if ($member->getServer() === $this) {
                $member->setServer(null);
            }
        }

        return $this;
    }
}
