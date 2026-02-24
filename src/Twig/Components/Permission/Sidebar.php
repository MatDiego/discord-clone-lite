<?php

namespace App\Twig\Components\Permission;

use App\Dto\ChannelOverridesCollection;
use App\Entity\Channel;
use App\Entity\Server;
use App\Service\ChannelPermissionService;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Permission:Sidebar')]
class Sidebar
{
    public Channel $channel;
    public Server $server;
    public ?string $selected = null;

    public function __construct(
        private readonly ChannelPermissionService $permissionService
    ) {
    }

    public function getOverrideGroups(): ChannelOverridesCollection
    {
        return $this->permissionService->getOverrideGroups($this->channel);
    }
}
