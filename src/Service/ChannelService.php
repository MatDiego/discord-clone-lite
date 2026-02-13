<?php

namespace App\Service;

use App\Dto\CreateChannelRequest;
use App\Entity\Channel;
use App\Entity\Server;
use App\Repository\ChannelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;

class ChannelService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ChannelRepository $channelRepository,
    ) {
    }

    public function getDefaultChannelForServer(Server $server): ?Channel
    {
        return $this->channelRepository->findFirstTextChannel($server);
    }

    public function createChannel(CreateChannelRequest $dto, Server $server): Channel
    {

        $channel = new Channel($dto->name, $server);
        $channel->setType($dto->type);
        $server->addChannel($channel);

        $this->em->persist($channel);

        $this->em->flush();
        return $channel;
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
