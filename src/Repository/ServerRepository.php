<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Server>
 */
class ServerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Server::class);
    }

    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin(ServerMember::class, 'sm', \Doctrine\ORM\Query\Expr\Join::ON, 'sm.server = s')
            ->andWhere('sm.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function add(Server $server): void
    {
        $this->getEntityManager()->persist($server);
    }

    public function remove(Server $server): void
    {
        $this->getEntityManager()->remove($server);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
