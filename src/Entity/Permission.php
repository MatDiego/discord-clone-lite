<?php

namespace App\Entity;

use App\Enum\UserPermissionEnum;
use App\Repository\PermissionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PermissionRepository::class)]
class Permission
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true, nullable: false)]
    private Uuid $id;

    #[ORM\Column(type: 'string', length: 50, nullable: false, enumType: UserPermissionEnum::class)]
    private UserPermissionEnum $name;

    public function __construct(UserPermissionEnum $name)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
    }


    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): UserPermissionEnum
    {
        return $this->name;
    }

    public function setName(UserPermissionEnum $name): void
    {
        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->name->value;
    }
}
