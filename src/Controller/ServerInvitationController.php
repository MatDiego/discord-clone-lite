<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Entity\ServerInvitation;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Voter\ServerVoter;
use App\Service\NotificationService;
use App\Service\ServerInvitationService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/invitations', requirements: ['serverId' => Requirement::UUID_V7])]
final class ServerInvitationController extends AbstractController
{
    public function __construct(
        private readonly ServerInvitationService $invitationService,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
    ) {
    }

    #[IsGranted(ServerVoter::CREATE_INVITE, subject: 'server')]
    #[Route('/send', name: 'app_server_invitation_send', methods: ['POST'])]
    public function send(
        Request $request,
        #[MapEntity(id: 'serverId')] Server $server,
        RateLimiterFactory $serverInvitationLimiter,
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $limiter = $serverInvitationLimiter->create($currentUser->getId()->toRfc4122());
        if (!$limiter->consume(1)->isAccepted()) {
            $this->addFlash('error', 'Wysłałeś zbyt wiele zaproszeń. Spróbuj ponownie za chwilę.');

            return $this->redirectToRoute('app_server_default_channel', ['serverId' => $server->getId()]);
        }

        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('server_invite_' . $server->getId()->toRfc4122(), $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        $username = trim((string) $request->request->get('username', ''));

        if ($username === '') {
            $this->addFlash('error', 'Podaj nazwę użytkownika.');

            return $this->redirectToRoute('app_server_default_channel', ['serverId' => $server->getId()]);
        }

        $recipient = $this->userRepository->findByUsername($username);

        if ($recipient === null) {
            $this->addFlash('error', sprintf('Użytkownik "%s" nie istnieje.', $username));

            return $this->redirectToRoute('app_server_default_channel', ['serverId' => $server->getId()]);
        }

        try {
            $this->invitationService->send($server, $currentUser, $recipient);
            $this->addFlash('success', sprintf('Zaproszenie do użytkownika %s zostało wysłane.', $recipient->getUsername()));
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_server_default_channel', ['serverId' => $server->getId()]);
    }

    #[Route('/{invitationId}/accept', name: 'app_invitation_accept', requirements: ['invitationId' => Requirement::UUID_V7], methods: ['POST'])]
    public function accept(
        Request $request,
        #[MapEntity(id: 'invitationId')] ServerInvitation $invitation,
    ): Response {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('invitation_accept_' . $invitation->getId()->toRfc4122(), $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->invitationService->accept($invitation, $currentUser);
            $joinedServer = $invitation->getServer();
            $this->addFlash('success', sprintf('Dołączyłeś do serwera %s!', $invitation->getServer()->getName()));
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $notifications = $this->notificationService->getUnreadNotifications($currentUser);
        return $this->render('notification/stream_badge.stream.html.twig', [
            'joinedServer' => $joinedServer ?? null,
            'notifications' => $notifications,
            'count' => count($notifications),
        ], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
    }


    #[Route('/{invitationId}/decline', name: 'app_invitation_decline', requirements: ['invitationId' => Requirement::UUID_V7], methods: ['POST'])]
    public function decline(
        Request $request,
        #[MapEntity(id: 'invitationId')] ServerInvitation $invitation,
    ): Response {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('invitation_decline_' . $invitation->getId()->toRfc4122(), $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->invitationService->decline($invitation, $currentUser);
            $joinedServer = $invitation->getServer();
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $notifications = $this->notificationService->getUnreadNotifications($currentUser);
        return $this->render('notification/stream_badge.stream.html.twig', [
            'joinedServer' => $joinedServer ?? null,
            'notifications' => $notifications,
            'count' => count($notifications),
        ], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
    }
}
