<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use Override;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class MercureNotificationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Authorization $authorization,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($event->getRequest()->getMethod() !== 'GET') {
            return;
        }

        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $userIdRfc = $user->getId()->toRfc4122();
        $topics = [
            sprintf('http://notifications/%s', $userIdRfc),
            sprintf('http://friends/%s', $userIdRfc),
            ];

        $serverId = $request->attributes->get('serverId');
        if ($serverId) {
            $topics[] = sprintf('http://servers/%s', $serverId);
        }

        $channelId = $request->attributes->get('channelId');
        if ($channelId) {
            $topics[] = sprintf('http://channels/%s', $channelId);
        }

        $this->authorization->setCookie($request, $topics);
    }

    #[Override]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -10],
        ];
    }
}
