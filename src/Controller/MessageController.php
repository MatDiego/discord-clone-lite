<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Message;
use App\Entity\Server;
use App\Entity\User;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use App\Security\Voter\ChannelVoter;
use App\Service\MessageManager;
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
        MessageManager $messageManager,
        MessageRepository $messageRepository
    ): Response
    {
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message, [
            'channel' => $channel ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var User $user */
            $user = $this->getUser();
            $messageManager->postMessage($message, $channel, $user);


            if ($request->getPreferredFormat() === TurboBundle::STREAM_FORMAT) {
                return new Response('');
            }
        }
        $messages = $messageRepository->findLatestByChannel($channel);
        $response = $this->render('server/view.html.twig',
        [
            'channel' => $channel,
            'server' => $server,
            'messages' => $messages,
        ]);

        $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);

        return $response;
    }

}
