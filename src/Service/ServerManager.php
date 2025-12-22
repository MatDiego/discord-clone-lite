<?php

namespace App\Service;

use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Enum\ChannelType;
use Doctrine\ORM\EntityManagerInterface;

class ServerManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function createServer(Server $server, User $owner): void
    {
        $server->setOwner($owner);

        $generalChannel = new Channel();
        $generalChannel->setName('ogólny');
        $generalChannel->setType(ChannelType::TEXT);
        $generalChannel->setServer($server);
        $server->addChannel($generalChannel);

        $member = new ServerMember();
        $member->setUser($owner);
        $member->setServer($server);
        $server->addMember($member);

        $this->em->persist($server);
        $this->em->persist($generalChannel);
        $this->em->persist($member);
        $this->em->flush();
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
