<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateMessageRequest;
use App\Entity\Channel;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

final readonly class MessageService
{
    public function __construct(
        private HubInterface $hub,
        private Environment $twig,
        private MessageRepository $messageRepository,
    ) {
    }

    public function getMessages(Channel $channel): array
    {
        return $this->messageRepository->findLatestByChannel($channel);
    }

    public function getMessagesAround(Channel $channel, ?Message $referenceMessage): array
    {
        return $this->messageRepository->findMessagesAround($channel, $referenceMessage);
    }

    public function getOlderMessages(Channel $channel, Message $referenceMessage, int $limit = 50): array
    {
        return $this->messageRepository->findBefore($channel, $referenceMessage, $limit);
    }

    public function getNewerMessages(Channel $channel, Message $referenceMessage, int $limit = 50): array
    {
        return $this->messageRepository->findAfter($channel, $referenceMessage, $limit);
    }

    public function getMessageById(string $id): ?Message
    {
        return $this->messageRepository->find($id);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function postMessage(CreateMessageRequest $dto, Channel $channel, User $user): void
    {
        $message = new Message($dto->content, $user, $channel);

        $this->messageRepository->add($message);
        $this->messageRepository->flush();


        $content = $this->twig->render('chat/message.stream.html.twig', [
            'message' => $message,
        ]);

        $topic = sprintf('http://channels/%s', $channel->getId());

        $update = new Update(
            $topic,
            $content,
            true
        );

        $this->hub->publish($update);
    }
}
