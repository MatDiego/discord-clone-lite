<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\MemberRoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MemberRoleRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_member_role', columns: ['server_member_id', 'role_id'])]
class MemberRole
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'memberRoles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private ServerMember $serverMember;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private UserRole $role;

    public function __construct(ServerMember $serverMember, UserRole $role)
    {
        $this->id = Uuid::v7();
        $this->serverMember = $serverMember;
        $this->role = $role;
    }


    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getServerMember(): ServerMember
    {
        return $this->serverMember;
    }

    public function setServerMember(ServerMember $serverMember): void
    {
        $this->serverMember = $serverMember;
    }


    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(UserRole $role): void
    {
        $this->role = $role;
    }



}
