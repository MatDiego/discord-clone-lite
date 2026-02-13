<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\User;
use App\Form\CreateServerType;
use App\Form\ServerType;
use App\Repository\ChannelRepository;
use App\Security\Voter\ServerVoter;
use App\Service\ServerService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers')]
final class ServerController extends AbstractController
{
    #[Route('/new', name: 'app_server_create')]
    public function create(
        Request $request,
        ServerService $serverService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(CreateServerType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $server = $serverService->createServer($form->getData(), $user);
            $defaultChannel = $server->getChannels()->first();

            $this->addFlash('success', 'Serwer został utworzony!');

            return $this->redirectToRoute('app_chat_channel', [
                'serverId' => $server->getId(),
                'channelId' => $defaultChannel->getId()
            ]);
        }

        return $this->render('server/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{serverId}/edit', name: 'app_server_edit', requirements: ['serverId' => Requirement::UUID_V7])]
    #[IsGranted(ServerVoter::EDIT, subject: 'server')]
    public function edit(
        Request $request,
        #[MapEntity(id: 'serverId')] Server $server,
        ServerService $serverService
    ): Response {

        $form = $this->createForm(ServerType::class, $server);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $serverService->updateServer();

            $this->addFlash('success', 'Ustawienia serwera zapisane.');

            return $this->redirectToRoute('app_server_edit', ['serverId' => $server->getId()]);
        }

        return $this->render('server/edit.html.twig', [
            'server' => $server,
            'form' => $form,
        ]);
    }

    #[Route('/{serverId}/delete', name: 'app_server_delete', requirements: ['serverId' => Requirement::UUID_V7], methods: ['POST'])]
    #[IsGranted(ServerVoter::DELETE, subject: 'server')]
    public function delete(
        Request $request,
        #[MapEntity(id: 'serverId')] Server $server,
        ServerService $serverService
    ): Response {

        if ($this->isCsrfTokenValid('delete_server_' . $server->getId(), $request->request->get('_csrf_token'))) {
            $serverService->removeServer($server);
            $this->addFlash('success', 'Serwer został pomyślnie usunięty.');
            return $this->redirectToRoute('app_dashboard');
        }

        $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
        return $this->redirectToRoute('app_server_edit', ['serverId' => $server->getId()]);
    }

}
