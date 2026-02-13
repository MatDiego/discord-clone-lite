<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Server;
use App\Form\ChannelType;
use App\Form\CreateChannelType;
use App\Security\Voter\ChannelVoter;
use App\Security\Voter\ServerVoter;
use App\Service\ChannelService;
use App\Service\MessageService;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/channels')]
final class ChannelController extends AbstractController
{
    #[Route('/{channelId}',
        name: 'app_chat_channel',
        requirements: ['serverId' => Requirement::UUID_V7, 'channelId' => Requirement::UUID_V7])]
    #[IsGranted(ChannelVoter::VIEW, subject: 'channel')]
    public function channelView(
        #[MapEntity(id: 'serverId')] Server $server,
        #[MapEntity(mapping: ['channelId' => 'id', 'serverId' => 'server'])] Channel $channel,
        MessageService $messageService,
        Authorization $authorization,
        Request $request,
    ): Response {
        $messages = $messageService->getMessages($channel);

        $topic = sprintf('http://channels/%s', $channel->getId());
        $authorization->setCookie($request, [$topic]);

        return $this->render('server/view.html.twig', [
            'server' => $server,
            'channel' => $channel,
            'messages' => $messages,
        ]);
    }

    /**
     * Przekierowanie po kliknięciu w ikonę serwera
     */
    #[Route('', name: 'app_server_default_channel', priority: -1)]
    #[IsGranted(ServerVoter::VIEW, subject: 'server')]
    public function defaultChannel(
        #[MapEntity(id: 'serverId')] Server $server,
        ChannelService $channelService,
    ): Response {
        $firstChannel = $channelService->getDefaultChannelForServer($server);

        if (!$firstChannel) {
            throw $this->createNotFoundException('Ten serwer nie ma dostępnych kanałów.');
        }

        return $this->redirectToRoute('app_chat_channel', [
            'serverId' => $server->getId(),
            'channelId' => $firstChannel->getId()
        ]);
    }

    #[Route('/create', name: 'app_channel_create', methods: ['GET', 'POST'])]
    #[IsGranted(ServerVoter::CREATE_CHANNEL, subject: 'server')]
    public function create(
        Request $request,
        ChannelService $channelService,
        #[MapEntity(id: 'serverId')] Server $server
    ): Response {
        $form = $this->createForm(CreateChannelType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $channel = $channelService->createChannel($form->getData(), $server);
            $this->addFlash('success', 'Kanał został utworzony!');

            return $this->redirectToRoute('app_chat_channel', [
                'serverId' => $server->getId(),
                'channelId' => $channel->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('channel/create.html.twig', [
            'form' => $form,
            'server' => $server,
        ]);
    }

    /**
     * @throws ORMException
     */
    #[Route('/{channelId}/edit', name: 'app_channel_edit', requirements: ['serverId' => Requirement::UUID_V7])]
    #[IsGranted(ChannelVoter::EDIT, subject: 'channel')]
    public function edit(
        Request $request,
        #[MapEntity(id: 'channelId')] Channel $channel,
        #[MapEntity(id: 'serverId')] Server $server,
        ChannelService $channelService
    ): Response {

        $form = $this->createForm(ChannelType::class, $channel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $channelService->updateChannel();

            $this->addFlash('success', 'Ustawienia kanału zapisane.');

            return $this->redirectToRoute('app_channel_edit', [
                'channelId' => $channel->getId(),
                'serverId' => $server->getId()
            ]);
        }

        $channelService->refresh($channel);

        return $this->render('channel/edit.html.twig', [
            'channel' => $channel,
            'server' => $server,
            'form' => $form,
        ]);
    }

    #[Route('/{channelId}/delete', name: 'app_channel_delete', requirements: ['serverId' => Requirement::UUID_V7], methods: ['POST'])]
    #[IsGranted(ChannelVoter::DELETE, subject: 'channel')]
    public function delete(
        Request $request,
        #[MapEntity(id: 'serverId')] Server $server,
        #[MapEntity(id: 'channelId')] Channel $channel,
        ChannelService $channelService
    ): Response {

        if ($this->isCsrfTokenValid('delete_channel_' . $channel->getId(), $request->request->get('_csrf_token'))) {
            $channelService->removeChannel($channel);
            $this->addFlash('success', 'Kanał został usunięty.');
            return $this->redirectToRoute('app_server_default_channel', [
                'serverId' => $server->getId()
            ]);
        }

        $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
        return $this->redirectToRoute('app_channel_edit', [
            'serverId' => $server->getId(),
            'channelId' => $channel->getId()
        ]);
    }
}
