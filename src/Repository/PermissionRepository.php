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

    /**
     * @param string[] $names
     * @return Permission[]
     */
    public function findByNames(array $names): array
    {
        $enums = [];
        foreach ($names as $name) {
            $enum = UserPermissionEnum::tryFrom($name);
            if ($enum) {
                $enums[] = $enum;
            }
        }

        if (empty($enums)) {
            return [];
        }

        return $this->createQueryBuilder('p')
            ->where('p.name IN (:names)')
            ->setParameter('names', $enums)
            ->getQuery()
            ->getResult();
    }
}
