<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Server;
use App\Entity\ServerBan;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServerBan>
 */
class ServerBanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerBan::class);
    }

    public function findActiveBan(User $user, Server $server): ?ServerBan
    {
        $now = new DateTimeImmutable();

        return $this->createQueryBuilder('b')
            ->where('b.user = :user')
            ->andWhere('b.server = :server')
            ->andWhere('b.expiresAt IS NULL OR b.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('server', $server)
            ->setParameter('now', $now)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function add(ServerBan $ban): void
    {
        $this->getEntityManager()->persist($ban);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
