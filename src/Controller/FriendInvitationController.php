<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FriendInvitation;
use App\Entity\User;
use App\Service\FriendInvitationService;
use App\Service\NotificationService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/friends')]
final class FriendInvitationController extends AbstractController
{
    public function __construct(
        private readonly FriendInvitationService $friendService,
        private readonly NotificationService $notificationService,
    ) {
    }

    #[Route('/invite', name: 'app_friend_invite_send', methods: ['POST'])]
    public function send(Request $request): Response
    {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('friend_invite', $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $username = trim((string) $request->request->get('username', ''));

        try {
            $this->friendService->sendByUsername($currentUser, $username);
            $this->addFlash('success', sprintf('Zaproszenie do %s zostało wysłane.', $username));
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $referer = $request->headers->get('referer');

        return $this->redirect($referer !== null ? $referer : $this->generateUrl('app_dashboard'));
    }

    #[Route('/{invitationId}/accept', name: 'app_friend_invite_accept', requirements: ['invitationId' => Requirement::UUID_V7], methods: ['POST'])]
    public function accept(
        Request $request,
        #[MapEntity(id: 'invitationId')] FriendInvitation $invitation,
    ): Response {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('friend_invite_accept_' . $invitation->getId()->toRfc4122(), $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->friendService->accept($invitation, $currentUser);
            $this->addFlash('success', sprintf('Jesteś teraz znajomym z %s!', $invitation->getSenderName()));
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('friends/stream_friend_accept.stream.html.twig', [], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
    }

    #[Route('/{invitationId}/decline', name: 'app_friend_invite_decline', requirements: ['invitationId' => Requirement::UUID_V7], methods: ['POST'])]
    public function decline(
        Request $request,
        #[MapEntity(id: 'invitationId')] FriendInvitation $invitation,
    ): Response {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('friend_invite_decline_' . $invitation->getId()->toRfc4122(), $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->friendService->decline($invitation, $currentUser);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $notifications = $this->notificationService->getUnreadNotifications($currentUser);

        return $this->render('notification/stream_badge.stream.html.twig', [
            'joinedServer' => null,
            'notifications' => $notifications,
            'count' => count($notifications),
        ], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
    }

    #[Route('/{invitationId}/remove', name: 'app_friend_remove', requirements: ['invitationId' => Requirement::UUID_V7], methods: ['POST'])]
    public function remove(
        Request $request,
        #[MapEntity(id: 'invitationId')] FriendInvitation $invitation,
    ): Response {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('friend_remove_' . $invitation->getId()->toRfc4122(), $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        try {
            $this->friendService->remove($invitation, $currentUser);
            $this->addFlash('success', 'Znajomy został usunięty.');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->render('friends/stream_friend_remove.stream.html.twig', [], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
    }

    #[Route('/widget', name: 'app_friend_list_widget', methods: ['GET'])]
    public function widget(): Response
    {
        return $this->render('friends/widget.html.twig');
    }
}
