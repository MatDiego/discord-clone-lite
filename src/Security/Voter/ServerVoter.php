<?php

namespace App\Security\Voter;

use App\Entity\Server;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ServerVoter extends Voter
{
    public const EDIT = 'SERVER_EDIT';
    public const VIEW = 'SERVER_VIEW';
    public const DELETE = 'SERVER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Server;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) return false;
        $server = $subject;

        return match($attribute) {
            self::EDIT, self::DELETE => $this->canEdit($server, $user),
            self::VIEW => $this->canView($server, $user),
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

        return $server->getMembers()->exists(fn($key, $member) => $member->getUser() === $user);
    }

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, Server::class, true);
    }
}
