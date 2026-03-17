<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\ServerInvitation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function add(Notification $notification): void
    {
        $this->getEntityManager()->persist($notification);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @return Notification[]
     */
    public function findUnreadForUser(User $user): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countUnread(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markReadByInvitation(ServerInvitation $invitation): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':read')
            ->where('n.invitation = :invitation')
            ->setParameter('read', true, Types::BOOLEAN)
            ->setParameter('invitation', $invitation)
            ->getQuery()
            ->execute();
    }

    public function markAllAsReadForUser(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':read')
            ->where('n.recipient = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('read', true, Types::BOOLEAN)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
