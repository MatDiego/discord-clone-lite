<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CreateMessageRequest;
use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\User;
use App\Form\CreateMessageType;
use App\Security\Voter\ChannelVoter;
use App\Service\ChannelReadStateService;
use App\Service\MessageService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\Turbo\TurboBundle;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/channels/{channelId}/messages')]
final class MessageController extends AbstractController
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/send', name: 'app_chat_send', methods: ['POST'])]
    #[IsGranted(ChannelVoter::SEND_MESSAGES, subject: 'channel')]
    public function send(
        Request $request,
        #[MapEntity(mapping: ['channelId' => 'id', 'serverId' => 'server'])] Channel $channel,
        #[MapEntity(id: 'serverId')] Server $server,
        MessageService $messageService,
        RateLimiterFactoryInterface $chatMessageLimiter,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(CreateMessageType::class, null, [
            'channel' => $channel,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var CreateMessageRequest $createMessageData */
            $createMessageData = $form->getData();

            $messageService->postMessage($createMessageData, $channel, $user);

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                return new Response('', Response::HTTP_OK, ['Content-Type' => TurboBundle::STREAM_MEDIA_TYPE]);
            }
        }

        $messages = $messageService->getMessages($channel);
        $response = $this->render(
            'server/view.html.twig',
            [
                'channel' => $channel,
                'server' => $server,
                'messages' => $messages,
            ]
        );

        $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);

        return $response;
    }

    #[Route('/older', name: 'app_chat_messages_older', methods: ['GET'])]
    #[IsGranted(ChannelVoter::VIEW_CHANNEL, subject: 'channel')]
    public function older(
        Request $request,
        #[MapEntity(mapping: ['channelId' => 'id', 'serverId' => 'server'])] Channel $channel,
        MessageService $messageService,
    ): Response {
        $beforeId = $request->query->get('before');

        if ($beforeId === null) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $referenceMessage = $messageService->getMessageById($beforeId);

        if (!$referenceMessage) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $messages = $messageService->getOlderMessages($channel, $referenceMessage);

        return $this->render('chat/message_chunk.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/newer', name: 'app_chat_messages_newer', methods: ['GET'])]
    #[IsGranted(ChannelVoter::VIEW_CHANNEL, subject: 'channel')]
    public function newer(
        Request $request,
        #[MapEntity(mapping: ['channelId' => 'id', 'serverId' => 'server'])] Channel $channel,
        MessageService $messageService,
    ): Response {
        $afterId = $request->query->get('after');

        if ($afterId === null) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $referenceMessage = $messageService->getMessageById($afterId);

        if (!$referenceMessage) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $messages = $messageService->getNewerMessages($channel, $referenceMessage);

        return $this->render('chat/message_chunk.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/read', name: 'app_chat_read', methods: ['POST'])]
    #[IsGranted(ChannelVoter::VIEW_CHANNEL, subject: 'channel')]
    public function updateReadState(
        Request $request,
        #[MapEntity(mapping: ['channelId' => 'id', 'serverId' => 'server'])] Channel $channel,
        MessageService $messageService,
        ChannelReadStateService $readStateService
    ): Response {
        /** @var array{lastReadId?: string} $content */
        $content = json_decode($request->getContent(), true);
        $lastReadId = $content['lastReadId'] ?? null;

        if ($lastReadId === null) {
            return new Response('Missing lastReadId', Response::HTTP_BAD_REQUEST);
        }

        $message = $messageService->getMessageById($lastReadId);

        if ($message && $message->getChannel() === $channel) {
            /** @var User $user */
            $user = $this->getUser();
            $readStateService->updateReadState($user, $channel, $message);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
