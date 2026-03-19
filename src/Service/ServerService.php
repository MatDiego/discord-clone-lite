<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\CreateServerRequest;
use App\Entity\Channel;
use App\Entity\RolePermission;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Entity\UserRole;
use App\Enum\UserPermissionEnum;
use App\Repository\ChannelRepository;
use App\Repository\PermissionRepository;
use App\Repository\ServerMemberRepository;
use App\Repository\ServerRepository;
use App\Repository\UserRoleRepository;

final readonly class ServerService
{
    public function __construct(
        private ServerRepository $serverRepository,
        private ChannelRepository $channelRepository,
        private ServerMemberRepository $serverMemberRepository,
        private UserRoleRepository $userRoleRepository,
        private PermissionRepository $permissionRepository,
        private MercureNotificationPublisher $mercurePublisher,
        private NotificationService $notificationService,
    ) {
    }

    public function createServer(CreateServerRequest $dto, User $owner): Server
    {
        $server = new Server($dto->name, $owner);
        $generalChannel = new Channel('ogólny', $server);
        $server->addChannel($generalChannel);

        $member = new ServerMember($server, $owner);

        $memberRole = new UserRole('Członek', 1, $server);
        $memberPermissions = $this->permissionRepository->findBy([
            'name' => [
                UserPermissionEnum::VIEW_CHANNELS,
                UserPermissionEnum::VIEW_CHANNEL,
                UserPermissionEnum::SEND_MESSAGES,
                UserPermissionEnum::CREATE_INVITE,
            ],
        ]);
        foreach ($memberPermissions as $permission) {
            $memberRole->addRolePermission(new RolePermission($memberRole, $permission));
        }

        $adminRole = new UserRole('Admin', 5, $server);
        $allPermissions = $this->permissionRepository->findAll();
        foreach ($allPermissions as $permission) {
            $adminRole->addRolePermission(new RolePermission($adminRole, $permission));
        }

        $this->serverRepository->add($server);
        $this->channelRepository->add($generalChannel);
        $this->serverMemberRepository->add($member);
        $this->userRoleRepository->add($memberRole);
        $this->userRoleRepository->add($adminRole);
        $this->serverRepository->flush();

        return $server;
    }

    public function removeServer(Server $server): void
    {
        $members = $this->serverMemberRepository->findByServerExcludingOwner($server);
        $memberUsers = array_map(fn (ServerMember $m) => $m->getUser(), $members);
        $serverName = $server->getName();

        foreach ($memberUsers as $user) {
            $this->notificationService->createServerDeletedNotification($user, $serverName);
        }

        $this->serverRepository->remove($server);
        $this->serverRepository->flush();

        $this->mercurePublisher->publishServerDeleted($memberUsers);
    }

    public function updateServer(): void
    {
        $this->serverRepository->flush();
    }
}
