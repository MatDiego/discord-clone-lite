<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ChannelOverridesCollection;
use App\Dto\OverrideGroupDTO;
use App\Entity\Channel;
use App\Entity\ChannelOverride;
use App\Enum\UserPermissionEnum;
use App\Repository\ChannelOverrideRepository;
use App\Repository\MemberRoleRepository;
use App\Repository\PermissionRepository;
use App\Repository\RolePermissionRepository;
use App\Repository\ServerMemberRepository;
use App\Repository\UserRoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final class ChannelPermissionService
{
    public function __construct(
        private readonly ChannelOverrideRepository $channelOverrideRepository,
        private readonly ServerMemberRepository $serverMemberRepository,
        private readonly UserRoleRepository $userRoleRepository,
        private readonly RolePermissionRepository $rolePermissionRepo,
        private readonly MemberRoleRepository $memberRoleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function normalizeUuid(string $raw): string
    {
        try {
            return Uuid::fromString($raw)->toRfc4122();
        } catch (InvalidArgumentException) {
            return $raw;
        }
    }

    public function targetExists(string $targetType, string $targetId): bool
    {
        return match ($targetType) {
            'role' => $this->userRoleRepository->find($targetId) !== null,
            'member' => $this->serverMemberRepository->find($targetId) !== null,
            default => false,
        };
    }

    /** Normalizes URL selection string (e.g. "ROLE:UUID" -> "role:uuid"). */
    public function normalizeSelected(?string $selected): ?string
    {
        if (null === $selected || '' === $selected || !str_contains($selected, ':')) {
            return null;
        }

        /** @var string $selected — guaranteed non-null after the guard above */
        $parts = explode(':', $selected, 2);

        if (count($parts) < 2) {
            return null;
        }

        [$type, $uuidStr] = $parts;
        $type = strtolower($type);

        if (!in_array($type, ['role', 'member'], true)) {
            return null;
        }

        try {
            return sprintf('%s:%s', $type, Uuid::fromString($uuidStr)->toRfc4122());
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /** Groups all channel overrides by target (role or member) into DTOs. */
    public function getOverrideGroups(Channel $channel): ChannelOverridesCollection
    {
        $allOverrides = $this->channelOverrideRepository->findByChannel($channel);
        $groups = [];

        foreach ($allOverrides as $override) {
            $role = $override->getRole();
            $member = $override->getServerMember();

            if ($role !== null) {
                $type = 'role';
                $targetId = $role->getId()->toRfc4122();
                $label = $role->getDisplayName();
            } elseif ($member !== null) {
                $type = 'member';
                $targetId = $member->getId()->toRfc4122();
                $label = $member->getDisplayName();
            } else {
                continue;
            }

            $key = $type . ':' . $targetId;

            if (!isset($groups[$key])) {
                $groups[$key] = new OverrideGroupDTO($type, $label, $targetId);
            }

            $groups[$key]->addPermission(
                $override->getPermissionValue(),
                $override->isAllow()
            );
        }

        return new ChannelOverridesCollection($groups);
    }

    /**
     * Resolves inherited permissions from roles, then applies
     * role-level channel overrides on top (for members only).
     */
    public function resolveInheritedPermissions(string $type, string $uuid, array $allPermissions, ?Channel $channel = null): array
    {
        if ($type === 'member') {
            $member = $this->serverMemberRepository->find($uuid);
            if (!$member)
                return [];

            if ($member->getServer()->getOwner() === $member->getUser()) {
                return array_fill_keys(array_map(fn(UserPermissionEnum $p): string => $p->value, $allPermissions), 'allow');
            }
            $roleIds = $this->memberRoleRepository->findRoleIdsByMember($member);
        } else {
            $roleIds = [Uuid::fromString($uuid)];
        }

        if (empty($roleIds)) {
            return [];
        }

        $pNames = $this->rolePermissionRepo->findPermissionNamesByRoleIds($roleIds);
        $inherited = array_fill_keys($pNames, 'allow');

        foreach ($allPermissions as $perm) {
            if (!isset($inherited[$perm->value])) {
                $inherited[$perm->value] = 'deny';
            }
        }

        if ($type === 'member' && $channel) {
            foreach ($this->channelOverrideRepository->findByChannel($channel) as $override) {
                $overrideRole = $override->getRole();
                if (!$overrideRole) {
                    continue;
                }

                $roleIdStrings = array_map(fn($id) => (string) $id, $roleIds);
                if (!in_array($overrideRole->getId()->toRfc4122(), $roleIdStrings, true)) {
                    continue;
                }

                $inherited[$override->getPermissionValue()] = $override->isAllow() ? 'allow' : 'deny';
            }
        }

        return $inherited;
    }

    /** Returns direct overrides for a specific target from a pre-built collection. */
    public function resolveEffectiveOverrides(
        string $type,
        string $uuid,
        ChannelOverridesCollection $overridesCollection
    ): array {
        $group = $overridesCollection->get("$type:$uuid");

        return $this->mapGroupToStatus($group);
    }

    private function mapGroupToStatus(?OverrideGroupDTO $group): array
    {
        if (!$group)
            return [];

        return array_map(
            fn(bool $isAllow) => $isAllow ? 'allow' : 'deny',
            $group->overrides
        );
    }

    /**
     * Clears existing overrides, then persists only those that differ from the inherited state.
     */
    public function saveTargetOverrides(Channel $channel, string $targetType, string $targetId, array $permissionsPayload): void
    {
        $role = null;
        $member = null;

        if ($targetType === 'role') {
            $role = $this->userRoleRepository->find($targetId);
        } elseif ($targetType === 'member') {
            $member = $this->serverMemberRepository->find($targetId);
        }

        if (!$role && !$member) {
            throw new InvalidArgumentException('Target not found');
        }

        $inherited = $this->resolveInheritedPermissions($targetType, $targetId, UserPermissionEnum::cases(), $channel);
        $this->clearTargetOverrides($channel, $targetType, $targetId);

        $permissionNames = array_keys($permissionsPayload);
        $permissionsList = $this->permissionRepository->findByNames($permissionNames);

        $permissionsMap = [];
        foreach ($permissionsList as $permission) {
            $permissionsMap[$permission->getName()->value] = $permission;
        }

        foreach ($permissionsPayload as $permName => $value) {
            if ($value !== 'allow' && $value !== 'deny') {
                continue;
            }

            if ($value === ($inherited[$permName] ?? 'deny')) {
                continue;
            }

            $permission = $permissionsMap[$permName] ?? null;
            if (!$permission) {
                continue;
            }

            $override = new ChannelOverride($channel, $role, $member, $permission);
            $override->setAllow($value === 'allow');
            $this->entityManager->persist($override);
        }

        $this->entityManager->flush();
    }

    public function clearTargetOverrides(Channel $channel, string $targetType, string $targetId): void
    {
        $this->channelOverrideRepository->deleteForTarget($channel, $targetType, $targetId);
        $this->channelOverridesCache = null;
    }
    }
}
