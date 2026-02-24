<?php

namespace App\Security\Voter;

use App\Entity\Server;
use App\Entity\User;
use App\Enum\UserPermissionEnum;
use App\Service\PermissionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ServerVoter extends Voter
{
    public const EDIT = 'SERVER_EDIT';
    public const VIEW = 'SERVER_VIEW';
    public const DELETE = 'SERVER_DELETE';
    public const CREATE_CHANNEL = 'SERVER_CREATE_CHANNEL';

    public function __construct(
        private readonly PermissionService $permissionService,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE, self::CREATE_CHANNEL])
            && $subject instanceof Server;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, mixed $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User)
            return false;

        /** @var Server $server */
        $server = $subject;

        return match ($attribute) {
            self::VIEW => $this->permissionService->hasServerPermission($user, $server, UserPermissionEnum::VIEW_CHANNELS),
            self::EDIT => $this->permissionService->hasServerPermission($user, $server, UserPermissionEnum::MANAGE_SERVER),
            self::DELETE => $this->permissionService->isOwner($user, $server),
            self::CREATE_CHANNEL => $this->permissionService->hasServerPermission($user, $server, UserPermissionEnum::MANAGE_CHANNELS),
            default => false,
        };
    }
}
