<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateChannelRequest;
use App\Entity\Channel;
use App\Entity\Server;
use App\Repository\ChannelRepository;
use Doctrine\ORM\Exception\ORMException;

final readonly class ChannelService
{
    public function __construct(
        private ChannelRepository $channelRepository,
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

        $this->channelRepository->add($channel);

        $this->channelRepository->flush();
        return $channel;
    }

    public function updateChannel(): void
    {
        $this->channelRepository->flush();
    }

    public function removeChannel(Channel $channel): void
    {
        $this->channelRepository->remove($channel);
        $this->channelRepository->flush();
    }

    /**
     * @throws ORMException
     */
    public function refresh(Channel $channel): void
    {
        $this->channelRepository->refresh($channel);
    }
}
