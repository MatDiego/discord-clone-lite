<?php

declare(strict_types=1);

namespace App\Twig\Components\Permission;

use App\Entity\Channel;
use App\Entity\Server;
use App\Repository\ServerMemberRepository;
use Symfony\Component\Uid\Uuid;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @psalm-suppress PropertyNotSetInConstructor — properties are populated by the Twig Component mount lifecycle.
 */
#[AsTwigComponent('Permission:TargetDropdown')]
final class TargetDropdown
{
    public Server $server;
    public Channel $channel;

    public function __construct(
        private readonly ServerMemberRepository $memberRepository
    ) {
    }

    public function getRoles(): array
    {
        return array_values(array_filter(
            array_map(function ($role) {
                return [
                    'id' => $role->getId() instanceof Uuid
                        ? $role->getId()->toRfc4122()
                        : (string) $role->getId(),
                    'name' => $role->getName(),
                ];
            }, $this->server->getUserRoles()->toArray()),

            fn($role) => $role['name'] !== 'Admin'
        ));
    }

    public function getMembers(): array
    {
        return $this->memberRepository->findByServerExcludingOwner($this->server);
    }
}
