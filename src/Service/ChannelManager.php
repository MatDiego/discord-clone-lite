<?php

namespace App\Service;

use App\Entity\Channel;
use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;

class ChannelManager
{

    public function __construct(private readonly EntityManagerInterface $em) {}
    public function saveChannel(Server $server, Channel $channel): void
    {

        $channel->setServer($server);
        $this->em->persist($channel);

        $this->em->flush();
    }

    public function updateChannel(): void
    {
        $this->em->flush();
    }

    public function removeChannel(Channel $channel): void
    {
        $this->em->remove($channel);
        $this->em->flush();
    }

    /**
     * @throws ORMException
     */
    public function refresh(Channel $channel): void
    {
        if ($this->em->contains($channel)) {
            $this->em->refresh($channel);
        }
    }
}
