<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\ServerMember;
use App\Entity\User;
use App\Enum\UserPermissionEnum;
use App\Service\PermissionService;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, ServerMember>
 */
final class ServerMemberVoter extends Voter
{
    public const KICK = 'MEMBER_KICK';
    public const BAN = 'MEMBER_BAN';

    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::KICK, self::BAN], true)
            && $subject instanceof ServerMember;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, mixed $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User)
            return false;

        $member = $subject;
        $targetUser = $member->getUser();
        $server = $member->getServer();

        if ($user->getId()->equals($targetUser->getId())) {
            return false;
        }

        if ($this->permissionService->isOwner($targetUser, $server)) {
            return false;
        }

        $permission = match ($attribute) {
            self::KICK => UserPermissionEnum::KICK_MEMBERS,
            self::BAN => UserPermissionEnum::BAN_MEMBERS,
            default => throw new \LogicException('This code should not be reached!'),
        };

        return $this->permissionService->isOwner($user, $server)
            || $this->permissionService->hasServerPermission($user, $server, $permission);
    }
}
