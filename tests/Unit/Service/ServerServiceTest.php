<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\CreateServerRequest;
use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Repository\ChannelRepository;
use App\Repository\ServerMemberRepository;
use App\Repository\ServerRepository;
use App\Service\ServerService;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ServerServiceTest extends TestCase
{
    private ServerRepository $serverRepository;
    private ChannelRepository $channelRepository;
    private ServerMemberRepository $serverMemberRepository;
    private ServerService $service;

    private User $owner;
    private Server $server;

    #[Override]
    protected function setUp(): void
    {
        $this->serverRepository = new class () extends ServerRepository {
            public array $servers = [];
            public bool $flushed = false;
            public function __construct()
            {}
            public function add(Server $server): void
            {
                $this->servers[] = $server;
            }
            public function remove(Server $server): void
            {
                $index = array_search($server, $this->servers, true);
                if ($index !== false) {
                    unset($this->servers[$index]);
                }
            }
            public function flush(): void
            {
                $this->flushed = true;
            }
        };

        $this->channelRepository = new class () extends ChannelRepository {
            public array $channels = [];
            public function __construct()
            {}
            public function add(Channel $channel): void
            {
                $this->channels[] = $channel;
            }
        };

        $this->serverMemberRepository = new class () extends ServerMemberRepository {
            public array $members = [];
            public function __construct()
            {}
            public function add(ServerMember $member): void
            {
                $this->members[] = $member;
            }
        };

        $this->service = new ServerService(
            $this->serverRepository,
            $this->channelRepository,
            $this->serverMemberRepository
        );

        $this->owner = new User('owner@example.com', 'OwnerUser', 'password');
        $this->server = new Server('Test Server', $this->owner);
    }

    #[Test]
    public function it_should_create_server_with_general_channel_and_owner_member(): void
    {
        // Arrange
        $dto = new CreateServerRequest();
        $dto->name = 'My Awesome Server';

        // Act
        $result = $this->service->createServer($dto, $this->owner);

        // Assert
        $this->assertCount(1, $this->serverRepository->servers);
        $this->assertCount(1, $this->channelRepository->channels);
        $this->assertCount(1, $this->serverMemberRepository->members);

        $savedServer = $this->serverRepository->servers[0];
        $savedChannel = $this->channelRepository->channels[0];
        $savedMember = $this->serverMemberRepository->members[0];

        $this->assertSame($savedServer, $result);

        $this->assertSame($dto->name, $savedServer->getName());
        $this->assertSame($this->owner, $savedServer->getOwner());

        $this->assertSame('ogólny', $savedChannel->getName());
        $this->assertSame($savedServer, $savedChannel->getServer());

        $this->assertSame($this->owner, $savedMember->getUser());
        $this->assertSame($savedServer, $savedMember->getServer());

        $this->assertTrue($this->serverRepository->flushed);
    }

    #[Test]
    public function it_should_remove_server_from_database(): void
    {
        // Arrange
        $this->serverRepository->servers[] = $this->server;
        $this->serverRepository->flushed = false;

        // Act
        $this->service->removeServer($this->server);

        // Assert
        $this->assertEmpty($this->serverRepository->servers);
        $this->assertTrue($this->serverRepository->flushed);
    }

    #[Test]
    public function it_should_flush_entity_manager_on_update(): void
    {
        // Arrange
        $this->serverRepository->flushed = false;

        // Act
        $this->service->updateServer();

        // Assert
        $this->assertTrue($this->serverRepository->flushed);
    }
}
