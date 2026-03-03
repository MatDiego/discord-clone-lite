<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Channel;
use App\Entity\ChannelOverride;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Enum\UserPermissionEnum;
use App\Repository\ChannelOverrideRepository;
use App\Repository\MemberRoleRepository;
use App\Repository\RolePermissionRepository;
use App\Repository\ServerMemberRepository;

final readonly class PermissionService
{
    public function __construct(
        private ServerMemberRepository $serverMemberRepository,
        private MemberRoleRepository $memberRoleRepository,
        private RolePermissionRepository $rolePermissionRepository,
        private ChannelOverrideRepository $channelOverrideRepository,
    ) {
    }

    public function hasServerPermission(User $user, Server $server, UserPermissionEnum $permission): bool
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        $member = $this->serverMemberRepository->findByUserAndServer($user, $server);
        if (null === $member) {
            return false;
        }

        return $this->memberHasPermission($member, $permission);
    }

    /**
     * Applies role permissions first, then channel overrides (role → member priority).
     */
    public function hasChannelPermission(User $user, Channel $channel, UserPermissionEnum $permission): bool
    {
        $server = $channel->getServer();

        if ($this->isOwner($user, $server)) {
            return true;
        }

        $member = $this->serverMemberRepository->findByUserAndServer($user, $server);
        if (null === $member) {
            return false;
        }

        $roleIds = $this->memberRoleRepository->findRoleIdsByMember($member);
        $granted = $this->rolePermissionRepository->hasPermissionInRoles($roleIds, $permission);
        $overrides = $this->channelOverrideRepository->findApplicableOverrides(
            $channel,
            $permission,
            $roleIds,
            $member,
        );

        return $this->applyOverrides($overrides, $member, $granted);
    }

    public function isOwner(User $user, Server $server): bool
    {
        return $server->getOwner() === $user;
    }

    private function memberHasPermission(ServerMember $member, UserPermissionEnum $permission): bool
    {
        $roleIds = $this->memberRoleRepository->findRoleIdsByMember($member);

        if (empty($roleIds)) {
            return false;
        }

        return $this->rolePermissionRepository->hasPermissionInRoles($roleIds, $permission);
    }

    /**
     * Priority (lowest to highest):
     *   1. Role overrides - DENY wins: if any role denies, result is false
     *   2. Member override - final word (at most one per permission per member)
     *
     * @param ChannelOverride[] $overrides
     */
    private function applyOverrides(array $overrides, ServerMember $member, bool $granted): bool
    {
        $roleHasAllow = false;
        $roleHasDeny = false;
        $memberOverride = null;

        foreach ($overrides as $override) {
            if ($override->getServerMember()?->getId()->equals($member->getId())) {
                $memberOverride = $override;
            } else {
                if ($override->isAllow()) {
                    $roleHasAllow = true;
                } else {
                    $roleHasDeny = true;
                }
            }
        }

        if ($roleHasDeny) {
            $granted = false;
        } elseif ($roleHasAllow) {
            $granted = true;
        }

        if ($memberOverride !== null) {
            $granted = $memberOverride->isAllow();
        }

        return $granted;
    }
}
