<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateServerRequest;
use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Repository\ChannelRepository;
use App\Repository\ServerMemberRepository;
use App\Repository\ServerRepository;

final readonly class ServerService
{
    public function __construct(
        private ServerRepository $serverRepository,
        private ChannelRepository $channelRepository,
        private ServerMemberRepository $serverMemberRepository,
    ) {
    }

    public function createServer(CreateServerRequest $dto, User $owner): Server
    {
        $server = new Server($dto->name, $owner);
        $generalChannel = new Channel('ogólny', $server);
        $server->addChannel($generalChannel);
        $member = new ServerMember($server, $owner);

        $this->serverRepository->add($server);
        $this->channelRepository->add($generalChannel);
        $this->serverMemberRepository->add($member);
        $this->serverRepository->flush();

        return $server;
    }

    public function removeServer(Server $server): void
    {
        $this->serverRepository->remove($server);
        $this->serverRepository->flush();
    }

    public function updateServer(): void
    {
        $this->serverRepository->flush();
    }
}
