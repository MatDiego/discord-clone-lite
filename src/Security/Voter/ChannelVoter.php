<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Channel;
use App\Entity\User;
use App\Enum\UserPermissionEnum;
use App\Service\PermissionService;
use Override;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Channel>
 */
final class ChannelVoter extends Voter
{
    public const EDIT_CHANNEL = 'CHANNEL_EDIT';
    public const VIEW_CHANNEL = 'CHANNEL_VIEW';
    public const DELETE_CHANNEL = 'CHANNEL_DELETE';
    public const SEND_MESSAGES = 'SEND_MESSAGES';
    public const ADD_MEMBER = 'ADD_MEMBER';
    public const MANAGE_PERMISSIONS = 'CHANNEL_MANAGE_PERMISSIONS';

    public function __construct(
        private readonly PermissionService $permissionService
    ) {
    }

    #[Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::EDIT_CHANNEL,
            self::VIEW_CHANNEL,
            self::DELETE_CHANNEL,
            self::SEND_MESSAGES,
            self::ADD_MEMBER,
            self::MANAGE_PERMISSIONS,
        ], true)
            && $subject instanceof Channel;
    }

    #[Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, mixed $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User)
            return false;
        $channel = $subject;

        return match ($attribute) {
            self::EDIT_CHANNEL, self::DELETE_CHANNEL => $this->permissionService->hasChannelPermission($user, $channel, UserPermissionEnum::MANAGE_CHANNEL),
            self::VIEW_CHANNEL => $this->permissionService->hasChannelPermission($user, $channel, UserPermissionEnum::VIEW_CHANNEL),
            self::SEND_MESSAGES => $this->permissionService->hasChannelPermission($user, $channel, UserPermissionEnum::SEND_MESSAGES),
            self::ADD_MEMBER => $this->permissionService->hasChannelPermission($user, $channel, UserPermissionEnum::ADD_MEMBER),
            self::MANAGE_PERMISSIONS => $this->permissionService->hasChannelPermission($user, $channel, UserPermissionEnum::MANAGE_PERMISSIONS),
            default => false,
        };
    }
}
