<?php

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
            ->innerJoin(ServerMember::class, 'sm', 'WITH', 'sm.server = s')
            ->andWhere('sm.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
