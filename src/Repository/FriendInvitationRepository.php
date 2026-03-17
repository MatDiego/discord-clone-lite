<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FriendInvitation;
use App\Entity\User;
use App\Enum\InvitationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FriendInvitation>
 */
class FriendInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FriendInvitation::class);
    }

    public function add(FriendInvitation $invitation): void
    {
        $this->getEntityManager()->persist($invitation);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function remove(FriendInvitation $invitation): void
    {
        $this->getEntityManager()->remove($invitation);
    }

    /**
     * @return FriendInvitation[]
     */
    public function findFriendsForUser(User $user): array
    {
        return $this->createQueryBuilder('fi')
            ->where('fi.sender = :user OR fi.recipient = :user')
            ->andWhere('fi.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', InvitationStatus::ACCEPTED)
            ->orderBy('fi.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return FriendInvitation[]
     */
    public function findPendingForRecipient(User $user): array
    {
        return $this->findBy([
            'recipient' => $user,
            'status'    => InvitationStatus::PENDING,
        ]);
    }

    public function findPendingBetween(User $a, User $b): ?FriendInvitation
    {
        return $this->createQueryBuilder('fi')
            ->where('(fi.sender = :a AND fi.recipient = :b) OR (fi.sender = :b AND fi.recipient = :a)')
            ->andWhere('fi.status = :status')
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setParameter('status', InvitationStatus::PENDING)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function areFriends(User $a, User $b): bool
    {
        $count = (int) $this->createQueryBuilder('fi')
            ->select('COUNT(fi.id)')
            ->where('(fi.sender = :a AND fi.recipient = :b) OR (fi.sender = :b AND fi.recipient = :a)')
            ->andWhere('fi.status = :status')
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setParameter('status', InvitationStatus::ACCEPTED)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
