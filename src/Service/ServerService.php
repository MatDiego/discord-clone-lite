<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateServerRequest;
use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Enum\ChannelTypeEnum;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ServerService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function createServer(CreateServerRequest $dto, User $owner): Server
    {
        $server = new Server($dto->name, $owner);
        $generalChannel = new Channel('ogólny', $server);
        $server->addChannel($generalChannel);
        $member = new ServerMember($server, $owner);

        $this->em->persist($server);
        $this->em->persist($generalChannel);
        $this->em->persist($member);
        $this->em->flush();

        return $server;
    }

    public function removeServer(Server $server): void
    {
        $this->em->remove($server);
        $this->em->flush();
    }

    public function updateServer(): void
    {
        $this->em->flush();
    }
}
