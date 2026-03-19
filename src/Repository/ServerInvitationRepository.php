<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Server;
use App\Entity\ServerInvitation;
use App\Entity\User;
use App\Enum\InvitationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServerInvitation>
 */
class ServerInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerInvitation::class);
    }

    public function add(ServerInvitation $invitation): void
    {
        $this->getEntityManager()->persist($invitation);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findPendingForServerAndRecipient(Server $server, User $recipient): ?ServerInvitation
    {
        return $this->findOneBy([
            'server'    => $server,
            'recipient' => $recipient,
            'status'    => InvitationStatus::PENDING,
        ]);
    }
}
