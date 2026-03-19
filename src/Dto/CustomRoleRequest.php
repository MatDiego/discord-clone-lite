<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\Permission;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

final class CustomRoleRequest
{
    #[Assert\NotBlank(message: 'role.name.not_blank')]
    #[Assert\Length(max: 30, maxMessage: 'role.name.max_length')]
    public string $name = '';

    /**
     * @var Collection<int, Permission>
     */
    #[Assert\Count(
        min: 1,
        minMessage: 'role.permission.min_count'
    )]
    public Collection $permissions;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): self
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }

        return $this;
    }

    public function removePermission(Permission $permission): self
    {
        $this->permissions->removeElement($permission);

        return $this;
    }
}
