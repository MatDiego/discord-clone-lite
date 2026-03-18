<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ChannelReadState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ChannelReadState>
 */
class ChannelReadStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChannelReadState::class);
    }

    public function add(ChannelReadState $channelReadState): void
    {
        $this->getEntityManager()->persist($channelReadState);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

}
