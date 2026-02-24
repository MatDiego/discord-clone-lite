<?php

namespace App\Repository;

use App\Entity\MemberRole;
use App\Entity\ServerMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<MemberRole>
 */
class MemberRoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MemberRole::class);
    }

    /**
     * @return Uuid[]
     */
    public function findRoleIdsByMember(ServerMember $member): array
    {
        $gb = $this->createQueryBuilder('mr');
        return $gb
            ->join('mr.role', 'ur')
            ->select('ur.id')
            ->andWhere('mr.serverMember = :member')
            ->setParameter('member', $member)
            ->getQuery()
            ->getSingleColumnResult();
    }
}
