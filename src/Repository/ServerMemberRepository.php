<?php

declare(strict_types=1);

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
    public function findByUserAndServer(User $user, Server $server): ?ServerMember
    {
        return $this->createQueryBuilder('sm')
            ->where('sm.user = :user')
            ->andWhere('sm.server = :server')
            ->setParameter('server', $server)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ServerMember[]
     */
    public function findByServer(Server $server): array
    {
        return $this->createQueryBuilder('sm')
            ->where('sm.server = :server')
            ->setParameter('server', $server)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ServerMember[]
     */
    public function findByServerExcludingOwner(Server $server): array
    {
        $qb = $this->createQueryBuilder('sm');
        return $qb
            ->where('sm.server = :server')
            ->andWhere('sm.user != :owner')
            ->setParameter('server', $server)
            ->setParameter('owner', $server->getOwner())
            ->getQuery()
            ->getResult();
    }

    public function add(ServerMember $member): void
    {
        $this->getEntityManager()->persist($member);
    }

    public function remove(ServerMember $member): void
    {
        $this->getEntityManager()->remove($member);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
