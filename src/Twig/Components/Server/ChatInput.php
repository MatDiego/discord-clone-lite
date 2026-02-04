<?php

namespace App\Twig\Components\Server;

use App\Entity\Channel;
use App\Entity\Server;
use App\Form\MessageType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Chat:Input')]
class ChatInput
{
    public Server $server;
    public Channel $channel;
    public FormView $form;

    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {}

    public function mount(Server $server, Channel $channel): void
    {
        $this->server = $server;
        $this->channel = $channel;

        $this->form = $this->formFactory
            ->create(MessageType::class, null, [
                'channel' => $channel
            ])
            ->createView();
    }
}
