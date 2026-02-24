<?php

namespace App\Repository;

use App\Entity\Channel;
use App\Entity\ChannelOverride;
use App\Entity\ServerMember;
use App\Enum\UserPermissionEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<ChannelOverride>
 */
class ChannelOverrideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChannelOverride::class);
    }

    /**
     * @param Uuid[] $roleIds
     * @return ChannelOverride[]
     */
    public function findOverridesForChannel(
        Channel $channel,
        UserPermissionEnum $permission,
        array $roleIds,
        ServerMember $member,
    ): array {
        $qb = $this->createQueryBuilder('cho')
            ->join('cho.permission', 'p')
            ->setParameter('permission', $permission)
            ->setParameter('channel', $channel)
            ->setParameter('member', $member)
            ->where('cho.channel = :channel')
            ->andWhere('p.name = :permission');

        if (empty($roleIds)) {
            $qb->andWhere('cho.serverMember = :member');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    'cho.serverMember = :member',
                    'cho.role IN (:roleIds)'
                )
            );
            $qb->setParameter('roleIds', $roleIds);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ChannelOverride[]
     */
    public function findByChannel(Channel $channel): array
    {
        return $this->createQueryBuilder('co')
            ->addSelect('p', 'r', 'sm', 'u')
            ->join('co.permission', 'p')
            ->leftJoin('co.role', 'r')
            ->leftJoin('co.serverMember', 'sm')
            ->leftJoin('sm.user', 'u')
            ->where('co.channel = :channel')
            ->setParameter('channel', $channel)
            ->addOrderBy('r.name', 'ASC')
            ->addOrderBy('u.username', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteForTarget(Channel $channel, string $targetType, string $targetId): void
    {
        $qb = $this->createQueryBuilder('co')
            ->delete()
            ->where('co.channel = :channel')
            ->setParameter('channel', $channel);

        if ($targetType === 'role') {
            $qb
                ->andWhere('IDENTITY(co.role) = :targetId')
                ->setParameter('targetId', $targetId);
        } else {
            $qb
                ->andWhere('IDENTITY(co.serverMember) = :targetId')
                ->setParameter('targetId', $targetId);
        }

        $qb->getQuery()->execute();
    }
}
