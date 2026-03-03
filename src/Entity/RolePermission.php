<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\RolePermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: RolePermissionRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_role_permission', columns: ['role_id', 'permission_id'])]
class RolePermission
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\ManyToOne(inversedBy: 'rolePermissions')]
    #[ORM\JoinColumn(nullable: false,onDelete: 'cascade')]
    private UserRole $role;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'cascade')]
    private Permission $permission;

    public function __construct(UserRole $role, Permission $permission)
    {
        $this->id = Uuid::v7();
        $this->role = $role;
        $this->permission = $permission;
    }


    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }
    public function getPermission(): Permission
    {
        return $this->permission;
    }
}
