<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Permission;
use App\Enum\UserPermissionEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Permission>
 */
class PermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Permission::class);
    }

    public function findByName(UserPermissionEnum $name): ?Permission
    {
        return $this->findOneBy(['name' => $name]);
    }
}
