<?php

declare(strict_types=1);


namespace App\Twig\Components\Server;

use App\Entity\Server;
use App\Entity\ServerMember;
use App\Repository\ServerMemberRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @psalm-suppress PropertyNotSetInConstructor — properties are populated by the Twig Component mount lifecycle.
 */
#[AsTwigComponent('Server:MemberList')]
final class MemberList
{
    public Server $server;

    /** @var ServerMember[] */
    public array $members = [];

    public function __construct(
        private readonly ServerMemberRepository $serverMemberRepository,
    ) {
    }

    public function mount(Server $server): void
    {
        $this->server = $server;
        $this->members = $this->serverMemberRepository->findByServer($server);
    }
}
