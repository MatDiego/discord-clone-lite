<?php

declare(strict_types=1);

namespace App\Twig\Components\Server;

use App\Entity\Channel;
use App\Entity\Server;
use App\Entity\User;
use App\Enum\UserPermissionEnum;
use App\Form\CreateMessageType;
use App\Service\PermissionService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @psalm-suppress PropertyNotSetInConstructor — properties are populated by the Twig Component mount lifecycle.
 */
#[AsTwigComponent('Chat:Input')]
final class ChatInput
{
    public Server $server;
    public Channel $channel;
    public FormView $form;
    public bool $canSendMessages = false;

    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly PermissionService $permissionService,
        private readonly Security $security,
    ) {
    }

    public function mount(Server $server, Channel $channel): void
    {
        $this->server = $server;
        $this->channel = $channel;

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $this->canSendMessages = $this->permissionService->hasChannelPermission(
                $user,
                $channel,
                UserPermissionEnum::SEND_MESSAGES
            );
        }

        $this->form = $this->formFactory
            ->create(CreateMessageType::class, null, [
                'channel' => $channel
            ])
            ->createView();
    }
}

