<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Channel;
use App\Entity\ChannelOverride;
use App\Entity\Permission;
use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Entity\UserRole;
use App\Enum\UserPermissionEnum;
use App\Repository\ChannelOverrideRepository;
use App\Repository\MemberRoleRepository;
use App\Repository\RolePermissionRepository;
use App\Repository\ServerMemberRepository;
use App\Service\PermissionService;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class PermissionServiceTest extends TestCase
{
    private ServerMemberRepository $serverMemberRepo;
    private MemberRoleRepository $memberRoleRepo;
    private RolePermissionRepository $rolePermissionRepo;
    private ChannelOverrideRepository $channelOverrideRepo;
    private PermissionService $service;

    private User $owner;
    private User $regularUser;
    private Server $server;
    private Channel $channel;
    private ServerMember $serverMember;

    #[Override]
    protected function setUp(): void
    {
        $this->serverMemberRepo = $this->createStub(ServerMemberRepository::class);
        $this->memberRoleRepo = $this->createStub(MemberRoleRepository::class);
        $this->rolePermissionRepo = $this->createStub(RolePermissionRepository::class);
        $this->channelOverrideRepo = $this->createStub(ChannelOverrideRepository::class);

        $this->service = new PermissionService(
            $this->serverMemberRepo,
            $this->memberRoleRepo,
            $this->rolePermissionRepo,
            $this->channelOverrideRepo,
        );

        $this->owner = new User('owner@test.com', 'Owner', 'password');
        $this->regularUser = new User('user@test.com', 'Regular', 'password');
        $this->server = new Server('Test Server', $this->owner);
        $this->channel = new Channel('general', $this->server);
        $this->serverMember = new ServerMember($this->server, $this->regularUser);
    }

    // ─── isOwner ─────────────────────────────────────────────

    #[Test]
    public function it_should_return_true_for_server_owner(): void
    {
        // Act
        $result = $this->service->isOwner($this->owner, $this->server);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_return_false_for_non_owner(): void
    {
        // Act
        $result = $this->service->isOwner($this->regularUser, $this->server);

        // Assert
        $this->assertFalse($result);
    }

    // ─── hasServerPermission ─────────────────────────────────────────────

    #[Test]
    public function it_should_grant_any_server_permission_to_owner(): void
    {
        // Act
        $result = $this->service->hasServerPermission($this->owner, $this->server, UserPermissionEnum::MANAGE_SERVER);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_deny_server_permissions_to_non_members(): void
    {
        // Arrange
        $this->serverMemberRepo->method('findByUserAndServer')->willReturn(null);

        // Act
        $result = $this->service->hasServerPermission($this->regularUser, $this->server, UserPermissionEnum::VIEW_CHANNELS);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_should_deny_permission_when_member_has_no_roles(): void
    {
        // Arrange
        $this->serverMemberRepo->method('findByUserAndServer')->willReturn($this->serverMember);
        $this->memberRoleRepo->method('findRoleIdsByMember')->willReturn([]);

        // Act
        $result = $this->service->hasServerPermission($this->regularUser, $this->server, UserPermissionEnum::SEND_MESSAGES);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_should_grant_permission_when_member_role_allows_it(): void
    {
        // Arrange
        $this->serverMemberRepo->method('findByUserAndServer')->willReturn($this->serverMember);
        $this->memberRoleRepo->method('findRoleIdsByMember')->willReturn([Uuid::v7()]);
        $this->rolePermissionRepo->method('hasPermissionInRoles')->willReturn(true);

        // Act
        $result = $this->service->hasServerPermission($this->regularUser, $this->server, UserPermissionEnum::MANAGE_CHANNELS);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_deny_permission_when_member_roles_lack_it(): void
    {
        // Arrange
        $this->serverMemberRepo->method('findByUserAndServer')->willReturn($this->serverMember);
        $this->memberRoleRepo->method('findRoleIdsByMember')->willReturn([Uuid::v7()]);
        $this->rolePermissionRepo->method('hasPermissionInRoles')->willReturn(false);

        // Act
        $result = $this->service->hasServerPermission($this->regularUser, $this->server, UserPermissionEnum::MANAGE_SERVER);

        // Assert
        $this->assertFalse($result);
    }

    // ─── hasServerPermission ─────────────────────────────────────────────

    #[Test]
    public function it_should_bypass_database_queries_for_server_owner(): void
    {
        // Arrange
        $serverMemberRepoMock = $this->createMock(ServerMemberRepository::class);
        $serverMemberRepoMock->expects($this->never())->method('findByUserAndServer');

        $service = new PermissionService(
            $serverMemberRepoMock,
            $this->memberRoleRepo,
            $this->rolePermissionRepo,
            $this->channelOverrideRepo,
        );

        // Act
        $service->hasServerPermission($this->owner, $this->server, UserPermissionEnum::MANAGE_SERVER);

        // Assert is implicit
    }

    // ─── hasChannelPermission ─────────────────────────────────────────────

    #[Test]
    public function it_should_grant_any_channel_permission_to_owner(): void
    {
        // Act
        $result = $this->service->hasChannelPermission($this->owner, $this->channel, UserPermissionEnum::SEND_MESSAGES);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_resolve_permission_from_role_when_no_overrides_exist(): void
    {
        // Arrange
        $this->stubMemberWithRoles(hasPermission: true);
        $this->channelOverrideRepo->method('findApplicableOverrides')->willReturn([]);

        // Act
        $result = $this->service->hasChannelPermission($this->regularUser, $this->channel, UserPermissionEnum::SEND_MESSAGES);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_deny_permission_when_role_override_explicitly_denies_it(): void
    {
        // Arrange
        $this->stubMemberWithRoles(hasPermission: true);

        $roleOverrideDeny = $this->createOverrideStub(serverMember: null, allow: false);
        $this->channelOverrideRepo->method('findApplicableOverrides')->willReturn([$roleOverrideDeny]);

        // Act
        $result = $this->service->hasChannelPermission($this->regularUser, $this->channel, UserPermissionEnum::SEND_MESSAGES);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_should_grant_permission_when_role_override_allows_it(): void
    {
        // Arrange
        $this->stubMemberWithRoles(hasPermission: false);

        $roleOverrideAllow = $this->createOverrideStub(serverMember: null, allow: true);
        $this->channelOverrideRepo->method('findApplicableOverrides')->willReturn([$roleOverrideAllow]);

        // Act
        $result = $this->service->hasChannelPermission($this->regularUser, $this->channel, UserPermissionEnum::SEND_MESSAGES);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_prioritize_deny_when_multiple_roles_have_conflicting_overrides(): void
    {
        // Arrange
        $this->stubMemberWithRoles(hasPermission: true);

        $roleAllow = $this->createOverrideStub(serverMember: null, allow: true);
        $roleDeny = $this->createOverrideStub(serverMember: null, allow: false);
        $this->channelOverrideRepo->method('findApplicableOverrides')->willReturn([$roleAllow, $roleDeny]);

        // Act
        $result = $this->service->hasChannelPermission($this->regularUser, $this->channel, UserPermissionEnum::SEND_MESSAGES);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_should_prioritize_member_allow_override_over_role_deny_override(): void
    {
        // Arrange
        $this->stubMemberWithRoles(hasPermission: true);

        $roleDeny = $this->createOverrideStub(serverMember: null, allow: false);
        $memberAllow = $this->createOverrideStub(serverMember: $this->serverMember, allow: true);
        $this->channelOverrideRepo->method('findApplicableOverrides')->willReturn([$roleDeny, $memberAllow]);

        // Act
        $result = $this->service->hasChannelPermission($this->regularUser, $this->channel, UserPermissionEnum::SEND_MESSAGES);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_prioritize_member_deny_override_over_any_allow_overrides(): void
    {
        // Arrange
        $this->stubMemberWithRoles(hasPermission: true);

        $roleAllow = $this->createOverrideStub(serverMember: null, allow: true);
        $memberDeny = $this->createOverrideStub(serverMember: $this->serverMember, allow: false);
        $this->channelOverrideRepo->method('findApplicableOverrides')->willReturn([$roleAllow, $memberDeny]);

        // Act
        $result = $this->service->hasChannelPermission($this->regularUser, $this->channel, UserPermissionEnum::SEND_MESSAGES);

        // Assert
        $this->assertFalse($result);
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function stubMemberWithRoles(bool $hasPermission): void
    {
        $this->serverMemberRepo->method('findByUserAndServer')->willReturn($this->serverMember);
        $this->memberRoleRepo->method('findRoleIdsByMember')->willReturn([Uuid::v7()]);
        $this->rolePermissionRepo->method('hasPermissionInRoles')->willReturn($hasPermission);
    }

    private function createOverrideStub(?ServerMember $serverMember, bool $allow): ChannelOverride
    {
        $role = $serverMember === null ? new UserRole('Stub Role', 1, $this->server) : null;
        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);
        $override = new ChannelOverride($this->channel, $role, $serverMember, $permission);
        $override->setAllow($allow);

        return $override;
    }
}
