<?php

namespace App\Security\Voter;

use App\Entity\Channel;
use App\Entity\User;
use App\Repository\ServerMemberRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ChannelVoter extends Voter
{
    public const EDIT = 'CHANNEL_EDIT';
    public const VIEW = 'CHANNEL_VIEW';
    public const DELETE = 'CHANNEL_DELETE';
    public const SEND_MESSAGE = 'CHANNEL_SEND_MESSAGE';

    public function __construct(
        private readonly ServerMemberRepository $repository)
    {
    }
    protected function supports(string $attribute, mixed $subject): bool
    {

        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::SEND_MESSAGE], true)
            && $subject instanceof Channel;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, mixed $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;
        $channel = $subject;

        return match($attribute) {
            self::EDIT, self::DELETE => $this->canEdit($channel, $user),
            self::VIEW => $this->canView($channel, $user),
            default => false,
        };
    }

    private function canEdit(Channel $channel, User $user): bool
    {
        return $channel->getServer()->getOwner() === $user;
    }

    private function canView(Channel $channel, User $user): bool
    {
        if ($this->canEdit($channel, $user)) {
            return true;
        }

        $server = $channel->getServer();
        return $this->repository->isUserInServer($user, $server);
    }
}
