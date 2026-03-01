<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Deprecated;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'user.email.unique')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\Column(length: 180, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    private string $email;

    #[ORM\Column(length: 50, nullable: false)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    private string $username;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles;

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: false)]
    #[Assert\NotBlank]
    private string $password;

    /**
     * @var Collection<int, Server>
     */
    #[ORM\OneToMany(targetEntity: Server::class, mappedBy: 'owner')]
    private Collection $ownedServers;

    #[ORM\OneToMany(targetEntity: ServerMember::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    private Collection $memberships;

    #[ORM\Column]
    private bool $isVerified = false;

    public function __construct(string $email, string $username, string $password)
    {
        $this->id = Uuid::v7();
        $this->email = $email;
        $this->username = $username;
        $this->password = $password;
        $this->roles = [];
        $this->ownedServers = new ArrayCollection();
        $this->memberships = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     *
     * @return non-empty-string
     */
    #[Override]
    public function getUserIdentifier(): string
    {
        assert($this->email !== '', 'User email must not be empty');

        return $this->email;
    }

    /**
     * @see UserInterface
     */
    #[Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    #[Override]
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[Deprecated]
    #[Override]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, Server>
     */
    public function getOwnedServers(): Collection
    {
        return $this->ownedServers;
    }

    public function addOwnedServer(Server $ownedServer): static
    {
        if (!$this->ownedServers->contains($ownedServer)) {
            $this->ownedServers->add($ownedServer);
            $ownedServer->setOwner($this);
        }

        return $this;
    }

    public function removeOwnedServer(Server $ownedServer): static
    {
        $this->ownedServers->removeElement($ownedServer);
        return $this;
    }

    public function getMemberships(): Collection
    {
        return $this->memberships;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
