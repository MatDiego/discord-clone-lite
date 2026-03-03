<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\CreateChannelRequest;
use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\User;
use App\Enum\ChannelTypeEnum;
use App\Repository\ChannelRepository;
use App\Service\ChannelService;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;


final class ChannelServiceTest extends TestCase
{
    private ChannelRepository $channelRepository;
    private ChannelService $service;

    private User $owner;
    private Server $server;
    private Channel $channel;

    #[Override]
    protected function setUp(): void
    {
        $this->channelRepository = $this->createMock(ChannelRepository::class);

        $this->service = new ChannelService($this->channelRepository);

        $this->owner = new User('owner@test.com', 'Owner', 'password');
        $this->server = new Server('Test Server', $this->owner);
        $this->channel = new Channel('general', $this->server);
    }

    #[Test]
    public function it_should_return_default_text_channel_for_server(): void
    {
        // Arrange
        $this->channelRepository
            ->method('findFirstTextChannel')
            ->with($this->server)
            ->willReturn($this->channel);

        // Act
        $result = $this->service->getDefaultChannelForServer($this->server);

        // Assert
        $this->assertSame($this->channel, $result);
    }

    #[Test]
    public function it_should_create_and_persist_new_channel(): void
    {
        // Arrange
        $dto = new CreateChannelRequest();
        $dto->name = 'Voice chat';
        $dto->type = ChannelTypeEnum::VOICE;

        $savedChannel = null;
        $this->channelRepository
            ->expects($this->once())
            ->method('add')
            ->willReturnCallback(function (Channel $channel) use (&$savedChannel): void {
                $savedChannel = $channel;
            });

        $this->channelRepository
            ->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->service->createChannel($dto, $this->server);

        // Assert
        $this->assertInstanceOf(Channel::class, $result);
        $this->assertSame('Voice chat', $result->getName());
        $this->assertSame(ChannelTypeEnum::VOICE, $result->getType());
        $this->assertTrue($this->server->getChannels()->contains($result));

        $this->assertNotNull($savedChannel);
        $this->assertSame('Voice chat', $savedChannel->getName());
        $this->assertSame(ChannelTypeEnum::VOICE, $savedChannel->getType());
        $this->assertSame($this->server, $savedChannel->getServer());
    }

    #[Test]
    public function it_should_flush_entity_manager_when_updating_channel(): void
    {
        // Arrange
        $this->channelRepository
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->service->updateChannel();

        // Assert is implicit
    }

    #[Test]
    public function it_should_remove_channel_and_flush(): void
    {
        // Arrange
        $this->channelRepository
            ->expects($this->once())
            ->method('remove')
            ->with($this->channel);

        $this->channelRepository
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->service->removeChannel($this->channel);

        // Assert is implicit
    }

    #[Test]
    public function it_should_refresh_channel_if_contained_in_entity_manager(): void
    {
        // Arrange
        $this->channelRepository
            ->expects($this->once())
            ->method('refresh')
            ->with($this->channel);

        // Act
        $this->service->refresh($this->channel);

        // Assert is implicit
    }
}
