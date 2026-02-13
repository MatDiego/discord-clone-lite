<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\User;
use App\Form\CreateMessageType;
use App\Security\Voter\ChannelVoter;
use App\Service\MessageService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    #[IsGranted(ChannelVoter::VIEW, subject: 'channel')]
    public function send(
        Request $request,
        #[MapEntity(mapping: ['channelId' => 'id', 'serverId' => 'server'])] Channel $channel,
        #[MapEntity(id: 'serverId')] Server $server,
        MessageService $messageService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(CreateMessageType::class, null, [
            'channel' => $channel,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageService->postMessage($form->getData(), $channel, $user);

            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                return new Response('', Response::HTTP_NO_CONTENT);
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
}
