<?php

declare(strict_types=1);

namespace App\Twig\Components\Friend;

use App\Entity\FriendInvitation;
use App\Entity\User;
use App\Repository\FriendInvitationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Friend:List')]
final class FriendList extends AbstractController
{
    /** @var FriendInvitation[] */
    public array $friends = [];

    /** @var FriendInvitation[] */
    public array $pendingReceived = [];

    public ?User $user = null;

    public function __construct(
        private readonly FriendInvitationRepository $friendRepository,
    ) {
    }

    public function mount(): void
    {
        /** @var ?User|null $user */
        $user = $this->user ?? ($this->getUser() instanceof User ? $this->getUser() : null);
        $this->user = $user;

        if ($user instanceof User) {
            $this->friends = $this->friendRepository->findFriendsForUser($user);
            $this->pendingReceived = $this->friendRepository->findPendingForRecipient($user);
        }
    }
}
