<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Server;
use App\Entity\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRole>
 */
class UserRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRole::class);
    }

    public function add(UserRole $entity): void
    {
        $this->getEntityManager()->persist($entity);
    }

    public function remove(UserRole $entity): void
    {
        $this->getEntityManager()->remove($entity);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
    public function findDefaultRoleForServer(Server $server): ?UserRole
    {
        return $this->findOneBy(['server' => $server, 'name' => 'Członek']);
    }
}
