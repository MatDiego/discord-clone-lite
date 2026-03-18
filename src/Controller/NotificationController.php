<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
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
#[Route('/notifications')]
final class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    #[Route('/dropdown', name: 'app_notifications_dropdown', methods: ['GET'])]
    public function dropdown(): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $notifications = $this->notificationService->getUnreadNotifications($currentUser);

        return $this->render('notification/dropdown.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/read-all', name: 'app_notifications_read_all', methods: ['POST'])]
    public function readAll(Request $request): Response
    {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('notifications_read_all', $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $this->notificationService->markAllAsRead($currentUser);

        return $this->render('notification/stream_badge.stream.html.twig', [
            'joinedServer' => null,
            'count' => 0
        ],
            new Response('', Response::HTTP_OK, ['Content-Type' => 'text/vnd.turbo-stream.html'])
        );
    }

    #[Route('/{notificationId}/read', name: 'app_notification_read', requirements: ['notificationId' => Requirement::UUID_V7], methods: ['POST'])]
    public function readOne(
        #[MapEntity(id: 'notificationId')] Notification $notification,
        Request $request,
    ): Response {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('notification_read_' . $notification->getId()->toRfc4122(), $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if (!$notification->getRecipient()->getId()->equals($currentUser->getId())) {
            throw $this->createAccessDeniedException();
        }

        $this->notificationService->markAsRead($notification);

        $notifications = $this->notificationService->getUnreadNotifications($currentUser);

        return $this->render('notification/stream_badge.stream.html.twig', [
            'joinedServer' => null,
            'notifications' => $notifications,
            'count' => count($notifications),
        ], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
    }
}
