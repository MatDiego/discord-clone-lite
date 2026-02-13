<?php

namespace App\Service;

use App\Dto\CreateMessageRequest;
use App\Entity\Channel;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class MessageService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly HubInterface $hub,
        private readonly Environment $twig,
        private readonly MessageRepository $messageRepository,
    ) {
    }

    public function getMessages(Channel $channel): array
    {
        return $this->messageRepository->findLatestByChannel($channel);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function postMessage(CreateMessageRequest $dto, Channel $channel, User $user): void
    {
        $message = new Message($dto->content, $user, $channel);

        $this->em->persist($message);
        $this->em->flush();


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
