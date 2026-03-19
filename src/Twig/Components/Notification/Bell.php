<?php

declare(strict_types=1);

namespace App\Twig\Components\Notification;

use App\Entity\User;
use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Notification:Bell')]
final class Bell extends AbstractController
{
    public int $count = 0;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    public function mount(): void
    {
        $user = $this->getUser();
        if ($user instanceof User) {
            $this->count = $this->notificationService->getUnreadCount($user);
        }
    }
}
