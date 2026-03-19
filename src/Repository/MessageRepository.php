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
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($limit)
            ->leftJoin('m.author', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();

        return array_reverse($messages);
    }

    /**
     * @return Message[]
     */
    public function findBefore(Channel $channel, Message $referenceMessage, int $limit = 50): array
    {
        $messages = $this->createQueryBuilder('m')
            ->andWhere('m.channel = :channel')
            ->andWhere('m.id < :referenceId')
            ->setParameter('channel', $channel)
            ->setParameter('referenceId', $referenceMessage->getId())
            ->orderBy('m.id', 'DESC')
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

    /**
     * @return Message[]
     */
    public function findAfter(Channel $channel, Message $referenceMessage, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.channel = :channel')
            ->andWhere('m.id > :referenceId')
            ->setParameter('channel', $channel)
            ->setParameter('referenceId', $referenceMessage->getId())
            ->orderBy('m.id', 'ASC') // sort ASC from reference
            ->setMaxResults($limit)
            ->leftJoin('m.author', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retrieves messages around a specific reference message.
     * @return Message[]
     */
    public function findMessagesAround(Channel $channel, ?Message $referenceMessage, int $olderLimit = 15, int $newerLimit = 35): array
    {
        if (!$referenceMessage) {
            return $this->findLatestByChannel($channel, 50);
        }

        $olderMessages = $this->createQueryBuilder('m')
            ->andWhere('m.channel = :channel')
            ->andWhere('m.id < :referenceId')
            ->setParameter('channel', $channel)
            ->setParameter('referenceId', $referenceMessage->getId())
            ->orderBy('m.id', 'DESC')
            ->setMaxResults($olderLimit)
            ->leftJoin('m.author', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();

        $olderMessages = array_reverse($olderMessages);

        $reference = $this->createQueryBuilder('m')
            ->andWhere('m.id = :referenceId')
            ->setParameter('referenceId', $referenceMessage->getId())
            ->leftJoin('m.author', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getOneOrNullResult();

        $referenceArr = $reference ? [$reference] : [];

        $newerMessages = $this->createQueryBuilder('m')
            ->andWhere('m.channel = :channel')
            ->andWhere('m.id > :referenceId')
            ->setParameter('channel', $channel)
            ->setParameter('referenceId', $referenceMessage->getId())
            ->orderBy('m.id', 'ASC')
            ->setMaxResults($newerLimit)
            ->leftJoin('m.author', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();

        return array_merge($olderMessages, $referenceArr, $newerMessages);
    }
}
