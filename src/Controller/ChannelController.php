<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Server;
use App\Enum\ChannelType;
use App\Repository\ChannelRepository;
use App\Repository\MessageRepository;
use App\Security\Voter\ServerVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/channels')]
final class ChannelController extends AbstractController
{


//    Implement private channels
    #[Route('/{channelId}',
        name: 'app_chat_channel',
        requirements: ['serverId' => Requirement::UUID_V7, 'channelId' => Requirement::UUID_V7])]
    #[IsGranted(ServerVoter::VIEW, subject: 'server')]
    public function channelView(
        #[MapEntity(id: 'serverId')] Server $server,
        #[MapEntity(mapping: ['channelId' => 'id', 'serverId' => 'server'])] Channel $channel,
        MessageRepository $messageRepository
    ): Response
    {

        $messages = $messageRepository->findLatestByChannel($channel);

        return $this->render('server/view.html.twig', [
            'currentServer' => $server,
            'currentChannel' => $channel,
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
        ChannelRepository $channelRepo
    ): Response
    {
        $firstChannel = $channelRepo->findOneBy([
            'server' => $server,
            'type' => ChannelType::TEXT
        ], ['createdAt' => 'ASC']);

        if (!$firstChannel) {
            throw $this->createNotFoundException('Ten serwer nie ma dostępnych kanałów.');
        }

        return $this->redirectToRoute('app_chat_channel', [
            'serverId' => $server->getId(),
            'channelId' => $firstChannel->getId()
        ]);
    }
}
