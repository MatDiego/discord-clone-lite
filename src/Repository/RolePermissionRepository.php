<?php

namespace App\Repository;

use App\Entity\RolePermission;
use App\Enum\UserPermissionEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<RolePermission>
 */
class RolePermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RolePermission::class);
    }

    /**
     * @param Uuid[] $roleIds
     */
    public function hasPermissionInRoles(array $roleIds, UserPermissionEnum $permission): bool
    {
        return (bool) $this->createQueryBuilder('rp')
            ->select('1')
            ->join('rp.permission', 'p')
            ->where('rp.role IN (:roleIds)')
            ->andWhere('p.name = :permission')
            ->setParameter('roleIds', $roleIds)
            ->setParameter('permission', $permission)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Uuid[] $roleIds
     * @return string[]
     */
    public function findPermissionNamesByRoleIds(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('rp')
            ->select('DISTINCT p.name')
            ->join('rp.permission', 'p')
            ->where('rp.role IN (:roleIds)')
            ->setParameter('roleIds', $roleIds)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'name');
    }
}
