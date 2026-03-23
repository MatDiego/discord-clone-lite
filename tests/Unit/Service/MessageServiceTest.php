<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\CreateMessageRequest;
use App\Entity\Channel;
use App\Entity\Message;
use App\Entity\Server;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\MercureNotificationPublisher;
use App\Service\MessageService;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;


final class MessageServiceTest extends TestCase
{
    private MercureNotificationPublisher $publisher;
    private MessageRepository $messageRepository;
    private MessageService $service;

    private User $author;
    private Server $server;
    private Channel $channel;

    #[Override]
    protected function setUp(): void
    {
        $this->publisher = $this->createMock(MercureNotificationPublisher::class);

        $this->messageRepository = new class () extends MessageRepository {
            public array $messages = [];
            public bool $flushed = false;
            public function __construct()
            {}
            public function findLatestByChannel(Channel $channel, int $limit = 50): array
            {
                $filtered = array_filter($this->messages, fn(Message $m) => $m->getChannel() === $channel);
                return array_slice($filtered, -$limit);
            }
            public function add(Message $message): void
            {
                $this->messages[] = $message;
            }
            public function flush(): void
            {
                $this->flushed = true;
            }
        };

        $this->service = new MessageService(
            $this->messageRepository,
            $this->publisher,
        );

        $this->author = new User('test@example.com', 'TestUser', 'password');
        $this->server = new Server('Test Server', $this->author);
        $this->channel = new Channel('general', $this->server);
    }

    #[Test]
    public function it_should_return_messages_from_repository_for_a_specific_channel(): void
    {
        // Arrange
        $message1 = new Message('Hello', $this->author, $this->channel);
        $message2 = new Message('World', $this->author, $this->channel);

        $this->messageRepository->messages = [$message1, $message2];
        $expectedMessages = [$message1, $message2];

        // Act
        $result = $this->service->getMessages($this->channel);

        // Assert
        $this->assertSame($expectedMessages, $result);
    }

    #[Test]
    public function it_should_persist_new_message_to_database(): void
    {
        // Arrange
        $dto = new CreateMessageRequest();
        $dto->content = 'This is a test message';

        // Act
        $this->service->postMessage($dto, $this->channel, $this->author);

        // Assert
        $this->assertCount(1, $this->messageRepository->messages);
        $savedMessage = $this->messageRepository->messages[0];

        $this->assertSame($dto->content, $savedMessage->getContent());
        $this->assertSame($this->author, $savedMessage->getAuthor());
        $this->assertSame($this->channel, $savedMessage->getChannel());
        $this->assertTrue($this->messageRepository->flushed);
    }

    #[Test]
    public function it_should_publish_message_via_mercure_publisher(): void
    {
        // Arrange
        $dto = new CreateMessageRequest();
        $dto->content = 'This is a test message';

        $this->publisher
            ->expects($this->once())
            ->method('publishMessage')
            ->with($this->callback(function (Message $message) use ($dto) {
                return $message->getContent() === $dto->content
                    && $message->getAuthor() === $this->author
                    && $message->getChannel() === $this->channel;
            }));

        // Act
        $this->service->postMessage($dto, $this->channel, $this->author);
    }
}
