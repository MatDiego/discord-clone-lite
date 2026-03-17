<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Server;
use App\Entity\User;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

final readonly class MercureNotificationPublisher
{
    public function __construct(
        private HubInterface $hub,
        private Environment $twig,
    ) {
    }

    public function publishBadge(User $user, int $count): void
    {
        $content = $this->twig->render('notification/stream_badge.stream.html.twig', [
            'count' => $count,
        ]);

        $topic = sprintf('http://notifications/%s', $user->getId()->toRfc4122());

        $this->hub->publish(new Update($topic, $content, true));
    }

    public function publishMemberJoined(Server $server): void
    {
        $content = $this->twig->render('notification/stream_member_list.stream.html.twig', [
            'server' => $server,
        ]);

        foreach ($server->getChannels() as $channel) {
            $topic = sprintf('http://channels/%s', $channel->getId());
            $this->hub->publish(new Update($topic, $content, true));
        }
    }

    public function publishFriendList(User $user): void
    {
        $content = $this->twig->render('friends/stream_friend_list.stream.html.twig', [
            'user' => $user,
        ]);

        $topic = sprintf('http://friends/%s', $user->getId()->toRfc4122());

        $this->hub->publish(new Update($topic, $content, true));
    }
}
