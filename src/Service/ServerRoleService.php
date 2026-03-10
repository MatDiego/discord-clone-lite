<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CustomRoleRequest;
use App\Entity\MemberRole;
use App\Entity\RolePermission;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\UserRole;
use App\Repository\MemberRoleRepository;
use App\Repository\UserRoleRepository;

final readonly class ServerRoleService
{
    public function __construct(
        private UserRoleRepository $userRoleRepository,
        private MemberRoleRepository $memberRoleRepository
    ) {
    }

    public function createCustomRole(Server $server, CustomRoleRequest $request): void
    {
        $maxPosition = 0;
        foreach ($server->getUserRoles() as $existingRole) {
            if ($existingRole->getPosition() > $maxPosition) {
                $maxPosition = $existingRole->getPosition();
            }
        }

        $role = new UserRole(
            $request->name,
            $maxPosition + 1,
            $server
        );

        foreach ($request->permissions as $permissionEntity) {
            $rolePermission = new RolePermission($role, $permissionEntity);
            $role->addRolePermission($rolePermission);
        }

        $this->userRoleRepository->add($role);
        $this->userRoleRepository->flush();
    }

    public function updateCustomRole(UserRole $role, CustomRoleRequest $request): void
    {
        $role->setName($request->name);

        $newPermissionIds = array_map(
            fn($p) => $p->getId()->toRfc4122(),
            $request->permissions->toArray()
        );

        foreach ($role->getRolePermissions()->toArray() as $rolePermission) {
            if (!in_array($rolePermission->getPermission()->getId()->toRfc4122(), $newPermissionIds, true)) {
                $role->removeRolePermission($rolePermission);
            }
        }

        $currentPermissionIds = array_map(
            fn($rp) => $rp->getPermission()->getId()->toRfc4122(),
            $role->getRolePermissions()->toArray()
        );

        foreach ($request->permissions as $permission) {
            if (!in_array($permission->getId()->toRfc4122(), $currentPermissionIds, true)) {
                $role->addRolePermission(new RolePermission($role, $permission));
            }
        }

        $this->userRoleRepository->flush();
    }

    /**
     * @return UserRole[]
     */
    public function getRolesForServer(Server $server): array
    {
        return $this->userRoleRepository->findBy(['server' => $server], ['position' => 'DESC']);
    }

    public function deleteRole(UserRole $role): void
    {
        $this->userRoleRepository->remove($role);
        $this->userRoleRepository->flush();
    }

    /**
     * @param UserRole[] $selectedRoles
     */
    public function updateMemberRoles(ServerMember $member, array $selectedRoles): void
    {
        $currentRoles = $member->getMemberRoles()->toArray();
        $selectedRoleIds = array_map(fn($r) => $r->getId()->toRfc4122(), $selectedRoles);

        foreach ($currentRoles as $memberRole) {
            if (!in_array($memberRole->getRole()->getId()->toRfc4122(), $selectedRoleIds, true)) {
                $member->removeMemberRole($memberRole);
                $this->memberRoleRepository->remove($memberRole);
            }
        }

        $currentRoleIds = array_map(fn($mr) => $mr->getRole()->getId()->toRfc4122(), $currentRoles);
        foreach ($selectedRoles as $role) {
            if (!in_array($role->getId()->toRfc4122(), $currentRoleIds, true)) {
                $newMemberRole = new MemberRole($member, $role);
                $member->addMemberRole($newMemberRole);
                $this->memberRoleRepository->add($newMemberRole);
            }
        }

        $this->memberRoleRepository->flush();
    }
}
