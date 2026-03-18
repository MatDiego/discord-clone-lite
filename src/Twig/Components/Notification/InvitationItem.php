<?php

declare(strict_types=1);

namespace App\Twig\Components\Notification;

use App\Entity\Notification;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Props are set by the UX Twig Component framework via prop binding (:notification="…"),
 * not via a constructor — a constructor would cause DI autowiring failure because
 * Doctrine entities are excluded from the service container.
 *
 * @psalm-suppress MissingConstructor
 */
#[AsTwigComponent]
final class InvitationItem
{
    public Notification $notification;

    public function getIcon(): string
    {
        $type = $this->notification->getType()->value;

        if ($this->notification->isServerDeleted() || in_array($type, ['kicked_from_server', 'banned_from_server'])) {
            return 'bi:exclamation-triangle-fill';
        }

        if (in_array($type, ['invitation_accepted', 'friend_invitation_accepted'])) {
            return 'bi:person-check-fill';
        }

        if ($type === 'friend_invitation') {
            return 'bi:person-plus-fill';
        }

        return 'bi:envelope-fill';
    }

    public function getIconColorClass(): string
    {
        $type = $this->notification->getType()->value;

        if ($this->notification->isServerDeleted() || in_array($type, ['kicked_from_server', 'banned_from_server'])) {
            return 'text-warning';
        }

        if (in_array($type, ['invitation_accepted', 'friend_invitation_accepted'])) {
            return 'text-success';
        }

        return 'text-primary';
    }

    public function getMessageHtml(): string
    {
        if ($this->notification->isServerDeleted()) {
            $serverName = htmlspecialchars($this->notification->getRelatedServerName());
            $serverBadge = sprintf('<span class="fw-semibold text-white">%s</span>', $serverName);

            return sprintf('Serwer %s został usunięty.', $serverBadge);
        }

        $type = $this->notification->getType()->value;
        $userName = htmlspecialchars($this->notification->getUserName());
        $userBadge = sprintf('<span class="fw-semibold text-white">%s</span>', $userName);

        if (in_array($type, ['server_invitation', 'invitation_accepted', 'kicked_from_server', 'banned_from_server'])) {
            $serverName = htmlspecialchars($this->notification->getRelatedServerName());
            $serverBadge = sprintf('<span class="fw-semibold text-white">%s</span>', $serverName);

            return match ($type) {
                'invitation_accepted' => sprintf('%s zaakceptował(a) Twoje zaproszenie na serwer %s.', $userBadge, $serverBadge),
                'kicked_from_server'  => sprintf('Zostałeś wyrzucony z serwera %s.', $serverBadge),
                'banned_from_server'  => sprintf('Zostałeś zbanowany na serwerze %s.', $serverBadge),
                default               => sprintf('%s zaprasza Cię na serwer %s.', $userBadge, $serverBadge),
            };
        }

        return match ($type) {
            'friend_invitation'          => sprintf('%s zaprasza Cię do znajomych.', $userBadge),
            'friend_invitation_accepted' => sprintf('%s zaakceptował(a) Twoje zaproszenie do znajomych.', $userBadge),
            default                      => 'Nowe powiadomienie.',
        };
    }
}
