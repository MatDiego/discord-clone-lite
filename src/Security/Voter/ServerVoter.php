<?php

namespace App\Security\Voter;

use App\Entity\Server;
use App\Entity\User;
use App\Repository\ServerMemberRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ServerVoter extends Voter
{
    public const EDIT = 'SERVER_EDIT';
    public const VIEW = 'SERVER_VIEW';
    public const DELETE = 'SERVER_DELETE';
    public const CREATE_CHANNEL = 'SERVER_CREATE_CHANNEL';

    public function __construct(
        private readonly ServerMemberRepository $repository)
    {
    }


    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::CREATE_CHANNEL])
            && $subject instanceof Server;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, mixed $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;
        $server = $subject;

        return match($attribute) {
            self::EDIT, self::DELETE => $this->canEdit($server, $user),
            self::VIEW => $this->canView($server, $user),
            self::CREATE_CHANNEL => $this->canCreateChannel($server, $user),
            default => false,
        };
    }

    private function canEdit(Server $server, User $user): bool
    {
        return $server->getOwner() === $user;
    }

    private function canView(Server $server, User $user): bool
    {
        if ($this->canEdit($server, $user)) {
            return true;
        }

        return $this->repository->isUserInServer($user, $server);
    }

    private function canCreateChannel(Server $server, User $user): bool
    {
        return $server->getOwner() === $user;
    }
}
