<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Channel;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[]
     */
    public function findLatestByChannel(Channel $channel, int $limit = 50): array
    {
        $messages = $this->createQueryBuilder('m')
            ->andWhere('m.channel = :channel')
            ->setParameter('channel', $channel)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->leftJoin('m.author', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();

        return array_reverse($messages);
    }

    public function add(Message $message): void
    {
        $this->getEntityManager()->persist($message);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
