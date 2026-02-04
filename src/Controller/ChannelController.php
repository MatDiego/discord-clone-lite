<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Server;
use App\Enum\ChannelTypeEnum;
use App\Form\ChannelType;
use App\Repository\ChannelRepository;
use App\Repository\MessageRepository;
use App\Security\Voter\ChannelVoter;
use App\Security\Voter\ServerVoter;
use App\Service\ChannelManager;
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
        MessageRepository $messageRepository,
        Authorization $authorization,
        Request $request,
    ): Response {
        $messages = $messageRepository->findLatestByChannel($channel);

        $topic = sprintf('http://channels/%s', $channel->getId());
        $authorization->setCookie($request, [$topic]);

        $response = $this->render('server/view.html.twig', [
            'server' => $server,
            'channel' => $channel,
            'messages' => $messages,
        ]);

        return $response;
    }

    /**
     * Przekierowanie po kliknięciu w ikonę serwera
     */
    #[Route('', name: 'app_server_default_channel', priority: -1)]
    #[IsGranted(ServerVoter::VIEW, subject: 'server')]
    public function defaultChannel(
        #[MapEntity(id: 'serverId')] Server $server,
        ChannelRepository $channelRepo
    ): Response {
        $firstChannel = $channelRepo->findOneBy([
            'server' => $server,
            'type' => ChannelTypeEnum::TEXT
        ], ['createdAt' => 'ASC']);

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
        ChannelManager $channelManager,
        #[MapEntity(id: 'serverId')] Server $server
    ): Response {
        $channel = new Channel();

        $form = $this->createForm(ChannelType::class, $channel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $channelManager->saveChannel($server, $channel);
            $this->addFlash('success', 'Kanał został utworzony!');

            return $this->redirectToRoute('app_chat_channel', [
                'serverId' => $server->getId(),
                'channelId' => $channel->getId()
            ]);
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
        ChannelManager $channelManager
    ): Response {

        $form = $this->createForm(ChannelType::class, $channel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $channelManager->updateChannel();

            $this->addFlash('success', 'Ustawienia kanału zapisane.');

            return $this->redirectToRoute('app_channel_edit', [
                'channelId' => $channel->getId(),
                'serverId' => $server->getId()
            ]);
        }

        $channelManager->refresh($channel);

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
        ChannelManager $channelManager
    ): Response {

        if ($this->isCsrfTokenValid('delete_channel_' . $channel->getId(), $request->request->get('_csrf_token'))) {
            $channelManager->removeChannel($channel);
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
