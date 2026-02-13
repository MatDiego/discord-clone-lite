<?php

namespace App\Repository;

use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServerMember>
 */
class ServerMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerMember::class);
    }

    public function isUserInServer(User $user, Server $server): bool
    {
        return (bool) $this->createQueryBuilder('sm')
            ->select('1')
            ->where('sm.user = :user')
            ->andWhere('sm.server = :server')
            ->setParameter('server', $server)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

}
