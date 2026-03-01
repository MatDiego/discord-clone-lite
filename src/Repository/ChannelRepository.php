<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Channel;
use App\Entity\Server;
use App\Enum\ChannelTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Channel>
 */
class ChannelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Channel::class);
    }

    public function findFirstTextChannel(Server $server): ?Channel
    {
        return $this->createQueryBuilder('c')
            ->where('c.server = :server')
            ->andWhere('c.type = :type')
            ->setParameter('server', $server)
            ->setParameter('type', ChannelTypeEnum::TEXT)
            ->orderBy('c.createdAt', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function add(Channel $channel): void
    {
        $this->getEntityManager()->persist($channel);
    }

    public function remove(Channel $channel): void
    {
        $this->getEntityManager()->remove($channel);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function refresh(Channel $channel): void
    {
        if ($this->getEntityManager()->contains($channel)) {
            $this->getEntityManager()->refresh($channel);
        }
    }
}
