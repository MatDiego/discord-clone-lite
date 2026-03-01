<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\CreateMessageRequest;
use App\Entity\Channel;
use App\Entity\Message;
use App\Entity\Server;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\MessageService;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;


final class MessageServiceTest extends TestCase
{
    private HubInterface $hub;
    private Environment $twig;
    private MessageRepository $messageRepository;
    private MessageService $service;

    private User $author;
    private Server $server;
    private Channel $channel;

    #[Override]
    protected function setUp(): void
    {
        $this->hub = $this->createMock(HubInterface::class);
        $this->twig = $this->createMock(Environment::class);

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
            $this->hub,
            $this->twig,
            $this->messageRepository,
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
    public function it_should_render_twig_template_for_the_new_message(): void
    {
        // Arrange
        $dto = new CreateMessageRequest();
        $dto->content = 'This is a test message';

        $this->twig
            ->expects($this->once())
            ->method('render')
            ->with(
                'chat/message.stream.html.twig',
                $this->callback(function (array $context) use ($dto) {
                    return isset($context['message'])
                        && $context['message'] instanceof Message
                        && $context['message']->getContent() === $dto->content;
                })
            )
            ->willReturn('<div>rendered</div>');

        // Act
        $this->service->postMessage($dto, $this->channel, $this->author);

        // Assert is implicit
    }

    #[Test]
    public function it_should_publish_rendered_message_to_mercure_hub(): void
    {
        // Arrange
        $dto = new CreateMessageRequest();
        $dto->content = 'This is a test message';
        $renderedHtml = '<div>rendered</div>';

        $this->twig->method('render')->willReturn($renderedHtml);

        $expectedTopic = sprintf('http://channels/%s', $this->channel->getId());

        $this->hub
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (Update $update) use ($expectedTopic, $renderedHtml) {
                return $update->getTopics() === [$expectedTopic]
                    && $update->getData() === $renderedHtml
                    && $update->isPrivate() === true;
            }));

        // Act
        $this->service->postMessage($dto, $this->channel, $this->author);

        // Assert is implicit
    }
}
