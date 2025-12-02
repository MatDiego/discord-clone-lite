<?php

namespace App\Controller;

use App\Entity\Channel;
use App\Entity\Server;
use App\Enum\ChannelType;
use App\Repository\ChannelRepository;
use App\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/app')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ChatController extends AbstractController
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
    ) {}


    #[Route(path: '/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('server/view.html.twig', [
            'currentServer' => null,
            'currentChannel' => null,
            'messages' => [],
        ]);
    }

    #[Route('/{server}/{channel}', name: 'app_chat_channel')]
    public function channelView(Server $server, Channel $channel): Response
    {
        if ($channel->getServer() !== $server) {
            throw $this->createNotFoundException('Kanał nie należy do tego serwera.');
        }

        $messages = $this->messageRepository->findLatestByChannel($channel);

        return $this->render('server/view.html.twig', [
            'currentServer' => $server,
            'currentChannel' => $channel,
            'messages' => $messages,
        ]);
    }

    /**
     * Przekierowanie po kliknięciu w ikonę serwera
     */
    #[Route('/{server}', name: 'app_chat_server_default', priority: -1)]
    public function defaultChannel(Server $server, ChannelRepository $channelRepo): Response
    {
        $firstChannel = $channelRepo->findOneBy([
            'server' => $server,
            'type' => ChannelType::TEXT
        ], ['createdAt' => 'ASC']);

        if (!$firstChannel) {
            throw $this->createNotFoundException('Ten serwer nie ma dostępnych kanałów.');
        }

        return $this->redirectToRoute('app_chat_channel', [
            'server' => $server->getId(),
            'channel' => $firstChannel->getId()
        ]);
    }
}
