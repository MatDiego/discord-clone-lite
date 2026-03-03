<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\ChannelOverridesCollection;
use App\Dto\OverrideGroupDTO;
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
use App\Repository\PermissionRepository;
use App\Repository\RolePermissionRepository;
use App\Repository\ServerMemberRepository;
use App\Repository\UserRoleRepository;
use App\Service\ChannelPermissionService;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ChannelPermissionServiceTest extends TestCase
{
    private ChannelOverrideRepository $channelOverrideRepo;
    private ServerMemberRepository $serverMemberRepo;
    private UserRoleRepository $userRoleRepo;
    private MemberRoleRepository $memberRoleRepo;
    private RolePermissionRepository $rolePermissionRepo;
    private PermissionRepository $permissionRepo;
    private ChannelPermissionService $service;

    private User $owner;
    private User $regularUser;
    private Server $server;
    private Channel $channel;
    private ServerMember $serverMember;

    #[Override]
    protected function setUp(): void
    {
        $this->channelOverrideRepo = $this->createMock(ChannelOverrideRepository::class);
        $this->serverMemberRepo = $this->createStub(ServerMemberRepository::class);
        $this->userRoleRepo = $this->createStub(UserRoleRepository::class);
        $this->memberRoleRepo = $this->createStub(MemberRoleRepository::class);
        $this->rolePermissionRepo = $this->createStub(RolePermissionRepository::class);
        $this->permissionRepo = $this->createStub(PermissionRepository::class);

        $this->service = new ChannelPermissionService(
            $this->channelOverrideRepo,
            $this->serverMemberRepo,
            $this->userRoleRepo,
            $this->rolePermissionRepo,
            $this->memberRoleRepo,
            $this->permissionRepo
        );

        $this->owner = new User('owner@test.com', 'Owner', 'password');
        $this->regularUser = new User('user@test.com', 'Regular', 'password');
        $this->server = new Server('Test Server', $this->owner);
        $this->channel = new Channel('general', $this->server);
        $this->serverMember = new ServerMember($this->server, $this->regularUser);
    }

    // ─── normalizeUuid ──────────────────────────────────────

    #[Test]
    public function it_should_return_unchanged_rfc4122_uuid_when_valid(): void
    {
        // Arrange
        $uuid = Uuid::v7();

        // Act
        $result = $this->service->normalizeUuid($uuid->toRfc4122());

        // Assert
        $this->assertSame($uuid->toRfc4122(), $result);
    }

    #[Test]
    public function it_should_convert_uppercase_uuid_to_lowercase_rfc4122(): void
    {
        // Arrange
        $uuid = Uuid::v7();
        $uppercase = strtoupper($uuid->toRfc4122());

        // Act
        $result = $this->service->normalizeUuid($uppercase);

        // Assert
        $this->assertSame($uuid->toRfc4122(), $result);
    }

    #[Test]
    public function it_should_return_original_string_when_uuid_is_invalid(): void
    {
        // Arrange is implicit

        // Act
        $result = $this->service->normalizeUuid('not-a-uuid');

        // Assert
        $this->assertSame('not-a-uuid', $result);
    }

    #[Test]
    public function it_should_return_empty_string_when_input_is_empty(): void
    {
        // Arrange is implicit

        // Act
        $result = $this->service->normalizeUuid('');

        // Assert
        $this->assertSame('', $result);
    }

    // ─── targetExists ───────────────────────────────────────

    #[Test]
    public function it_should_return_true_when_role_exists_in_database(): void
    {
        // Arrange
        $role = new UserRole('Admin', 1, $this->server);
        $this->userRoleRepo->method('find')->willReturn($role);

        // Act
        $result = $this->service->targetExists('role', Uuid::v7()->toRfc4122());

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_return_false_when_role_is_missing(): void
    {
        // Arrange
        $this->userRoleRepo->method('find')->willReturn(null);

        // Act
        $result = $this->service->targetExists('role', Uuid::v7()->toRfc4122());

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_should_return_true_when_member_exists_in_database(): void
    {
        // Arrange
        $this->serverMemberRepo->method('find')->willReturn($this->serverMember);

        // Act
        $result = $this->service->targetExists('member', Uuid::v7()->toRfc4122());

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_should_return_false_when_member_is_missing(): void
    {
        // Arrange
        $this->serverMemberRepo->method('find')->willReturn(null);

        // Act
        $result = $this->service->targetExists('member', Uuid::v7()->toRfc4122());

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_should_return_false_for_unknown_target_type(): void
    {
        // Arrange is implicit

        // Act
        $result = $this->service->targetExists('invalid', Uuid::v7()->toRfc4122());

        // Assert
        $this->assertFalse($result);
    }

    // ─── normalizeSelected ──────────────────────────────────

    #[Test]
    public function it_should_normalize_valid_role_selection_string(): void
    {
        // Arrange
        $uuid = Uuid::v7();

        // Act
        $result = $this->service->normalizeSelected('role:' . $uuid->toRfc4122());

        // Assert
        $this->assertSame('role:' . $uuid->toRfc4122(), $result);
    }

    #[Test]
    public function it_should_normalize_valid_member_selection_string(): void
    {
        // Arrange
        $uuid = Uuid::v7();

        // Act
        $result = $this->service->normalizeSelected('member:' . $uuid->toRfc4122());

        // Assert
        $this->assertSame('member:' . $uuid->toRfc4122(), $result);
    }

    #[Test]
    public function it_should_convert_target_type_to_lowercase(): void
    {
        // Arrange
        $uuid = Uuid::v7();

        // Act
        $result = $this->service->normalizeSelected('ROLE:' . $uuid->toRfc4122());

        // Assert
        $this->assertSame('role:' . $uuid->toRfc4122(), $result);
    }

    #[Test]
    public function it_should_return_null_when_selection_is_null(): void
    {
        // Arrange is implicit

        // Act
        $result = $this->service->normalizeSelected(null);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_should_return_null_when_selection_is_empty(): void
    {
        // Arrange is implicit

        // Act
        $result = $this->service->normalizeSelected('');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_should_return_null_when_selection_format_is_invalid(): void
    {
        // Arrange is implicit

        // Act
        $result = $this->service->normalizeSelected('no-colon-here');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_should_return_null_when_target_type_is_not_supported(): void
    {
        // Arrange
        $uuid = Uuid::v7();

        // Act
        $result = $this->service->normalizeSelected('banana:' . $uuid->toRfc4122());

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_should_return_null_when_selection_uuid_is_invalid(): void
    {
        // Arrange is implicit

        // Act
        $result = $this->service->normalizeSelected('role:not-a-uuid');

        // Assert
        $this->assertNull($result);
    }

    // ─── saveTargetOverrides ────────────────────────────────

    #[Test]
    public function it_should_throw_exception_when_saving_for_non_existent_target(): void
    {
        // Arrange
        $this->userRoleRepo->method('find')->willReturn(null);
        $this->serverMemberRepo->method('find')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);

        // Act & Assert
        $this->service->saveTargetOverrides($this->channel, 'role', Uuid::v7()->toRfc4122(), []);
    }

    #[Test]
    public function it_should_skip_saving_override_when_permission_matches_inherited_state(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn(['SEND_MESSAGES']);

        $this->channelOverrideRepo->expects($this->never())->method('add');

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'allow'],
        );

        // Assert is implicit
    }

    #[Test]
    public function it_should_persist_new_override_when_it_differs_from_inherited_state(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);

        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn(['SEND_MESSAGES']);
        $this->permissionRepo->method('findByNames')->willReturn([$permission]);

        $savedOverride = null;
        $this->channelOverrideRepo
            ->expects($this->once())
            ->method('add')
            ->willReturnCallback(function (ChannelOverride $override) use (&$savedOverride) {
                $savedOverride = $override;
            });

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'deny'],
        );

        // Assert
        $this->assertNotNull($savedOverride);
        $this->assertFalse($savedOverride->isAllow());
    }

    #[Test]
    public function it_should_ignore_invalid_permission_states_like_banana(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);

        $this->channelOverrideRepo->expects($this->never())->method('add');

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'banana'],
        );

        // Assert is implicit
    }

    #[Test]
    public function it_should_ignore_unknown_permission_names(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);

        $this->channelOverrideRepo->expects($this->never())->method('add');

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['TOTALLY_FAKE_PERMISSION' => 'allow'],
        );

        // Assert is implicit
    }

    #[Test]
    public function it_should_ignore_permissions_that_do_not_exist_in_database(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);
        $this->permissionRepo->method('findByNames')->willReturn([]);

        $this->channelOverrideRepo->expects($this->never())->method('add');

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'allow'],
        );

        // Assert is implicit
    }

    #[Test]
    public function it_should_successfully_save_overrides_for_member_targets(): void
    {
        // Arrange
        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);

        $this->serverMemberRepo->method('find')->willReturn($this->serverMember);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->memberRoleRepo->method('findRoleIdsByMember')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);
        $this->permissionRepo->method('findByNames')->willReturn([$permission]);

        $savedOverride = null;
        $this->channelOverrideRepo
            ->expects($this->once())
            ->method('add')
            ->willReturnCallback(function (ChannelOverride $override) use (&$savedOverride) {
                $savedOverride = $override;
            });

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'member',
            $this->serverMember->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'allow'],
        );

        // Assert
        $this->assertNotNull($savedOverride);
        $this->assertTrue($savedOverride->isAllow());
        $this->assertSame($this->serverMember, $savedOverride->getServerMember());
    }

    #[Test]
    public function it_should_flush_entity_manager_after_saving_overrides(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);

        $this->channelOverrideRepo->expects($this->atLeastOnce())->method('flush');

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            [],
        );

        // Assert is implicit
    }

    // ─── clearTargetOverrides ───────────────────────────────

    #[Test]
    public function it_should_delete_all_overrides_for_a_specific_role(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $roleId = $role->getId()->toRfc4122();

        $this->channelOverrideRepo
            ->expects($this->once())
            ->method('deleteForTarget')
            ->with($this->channel, 'role', $roleId);

        // Act
        $this->service->clearTargetOverrides($this->channel, 'role', $roleId);

        // Assert is implicit
    }

    #[Test]
    public function it_should_delete_all_overrides_for_a_specific_member(): void
    {
        // Arrange
        $memberId = $this->serverMember->getId()->toRfc4122();

        $this->channelOverrideRepo
            ->expects($this->once())
            ->method('deleteForTarget')
            ->with($this->channel, 'member', $memberId);

        // Act
        $this->service->clearTargetOverrides($this->channel, 'member', $memberId);

        // Assert is implicit
    }

    #[Test]
    public function it_should_execute_delete_query_even_if_no_overrides_exist_in_memory(): void
    {
        // Arrange
        $uuid = Uuid::v7()->toRfc4122();

        $this->channelOverrideRepo
            ->expects($this->once())
            ->method('deleteForTarget')
            ->with($this->channel, 'role', $uuid);

        // Act
        $this->service->clearTargetOverrides($this->channel, 'role', $uuid);

        // Assert is implicit
    }

    // ─── resolveInheritedPermissions ────────────────────────

    #[Test]
    public function it_should_return_empty_inherited_permissions_for_missing_member(): void
    {
        // Arrange
        $this->serverMemberRepo->method('find')->willReturn(null);

        // Act
        $result = $this->service->resolveInheritedPermissions(
            'member',
            Uuid::v7()->toRfc4122(),
            UserPermissionEnum::cases(),
        );

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function it_should_grant_all_permissions_to_server_owner(): void
    {
        // Arrange
        $ownerMember = new ServerMember($this->server, $this->owner);
        $this->serverMemberRepo->method('find')->willReturn($ownerMember);

        // Act
        $result = $this->service->resolveInheritedPermissions(
            'member',
            $ownerMember->getId()->toRfc4122(),
            UserPermissionEnum::cases(),
        );

        // Assert
        foreach (UserPermissionEnum::cases() as $perm) {
            $this->assertSame('allow', $result[$perm->value], "Owner should have 'allow' for {$perm->value}");
        }
    }

    #[Test]
    public function it_should_resolve_base_role_permissions_for_member(): void
    {
        // Arrange
        $this->serverMemberRepo->method('find')->willReturn($this->serverMember);
        $roleId = Uuid::v7();
        $this->memberRoleRepo->method('findRoleIdsByMember')->willReturn([$roleId]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn(['SEND_MESSAGES']);

        // Act
        $result = $this->service->resolveInheritedPermissions(
            'member',
            $this->serverMember->getId()->toRfc4122(),
            UserPermissionEnum::cases(),
        );

        // Assert
        $this->assertSame('allow', $result['SEND_MESSAGES']);
        foreach (UserPermissionEnum::cases() as $perm) {
            if ($perm->value !== 'SEND_MESSAGES') {
                $this->assertSame('deny', $result[$perm->value], "{$perm->value} should be 'deny'");
            }
        }
    }

    #[Test]
    public function it_should_return_empty_inherited_permissions_when_member_has_no_roles(): void
    {
        // Arrange
        $this->serverMemberRepo->method('find')->willReturn($this->serverMember);
        $this->memberRoleRepo->method('findRoleIdsByMember')->willReturn([]);

        // Act
        $result = $this->service->resolveInheritedPermissions(
            'member',
            $this->serverMember->getId()->toRfc4122(),
            UserPermissionEnum::cases(),
        );

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function it_should_resolve_base_permissions_for_role_target(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn(['SEND_MESSAGES']);

        // Act
        $result = $this->service->resolveInheritedPermissions(
            'role',
            $role->getId()->toRfc4122(),
            UserPermissionEnum::cases(),
        );

        // Assert
        $this->assertSame('allow', $result['SEND_MESSAGES']);
    }

    #[Test]
    public function it_should_apply_role_level_channel_overrides_when_resolving_member_permissions(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $roleId = $role->getId();

        $this->serverMemberRepo->method('find')->willReturn($this->serverMember);
        $this->memberRoleRepo->method('findRoleIdsByMember')->willReturn([$roleId]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn(['SEND_MESSAGES']);

        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);
        $overrideStub = clone new ChannelOverride($this->channel, $role, null, $permission);
        $overrideStub->setAllow(false);

        $this->channelOverrideRepo->method('findByChannel')->willReturn([$overrideStub]);

        // Act
        $result = $this->service->resolveInheritedPermissions(
            'member',
            $this->serverMember->getId()->toRfc4122(),
            UserPermissionEnum::cases(),
            $this->channel,
        );

        // Assert
        $this->assertSame('deny', $result['SEND_MESSAGES']);
    }

    // ─── getOverrideGroups ─────────────────────────────────

    #[Test]
    public function it_should_group_channel_overrides_by_role_target(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);

        $override = clone new ChannelOverride($this->channel, $role, null, $permission);
        $override->setAllow(true);

        $this->channelOverrideRepo->method('findByChannel')->willReturn([$override]);

        // Act
        $collection = $this->service->getOverrideGroups($this->channel);

        // Assert
        $key = 'role:' . $role->getId()->toRfc4122();
        $group = $collection->get($key);

        $this->assertNotNull($group);
        $this->assertSame('role', $group->type);
        $this->assertSame('Moderator', $group->label);
        $this->assertArrayHasKey('SEND_MESSAGES', $group->overrides);
        $this->assertTrue($group->overrides['SEND_MESSAGES']);
    }

    #[Test]
    public function it_should_group_channel_overrides_by_member_target(): void
    {
        // Arrange
        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);
        $override = clone new ChannelOverride($this->channel, null, $this->serverMember, $permission);
        $override->setAllow(false);

        $this->channelOverrideRepo->method('findByChannel')->willReturn([$override]);

        // Act
        $collection = $this->service->getOverrideGroups($this->channel);

        // Assert
        $key = 'member:' . $this->serverMember->getId()->toRfc4122();
        $group = $collection->get($key);

        $this->assertNotNull($group);
        $this->assertSame('member', $group->type);
        $this->assertArrayHasKey('SEND_MESSAGES', $group->overrides);
        $this->assertFalse($group->overrides['SEND_MESSAGES']);
    }

    #[Test]
    public function it_should_return_empty_collection_when_channel_has_no_overrides(): void
    {
        // Arrange
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);

        // Act
        $collection = $this->service->getOverrideGroups($this->channel);

        // Assert
        $this->assertNull($collection->get('role:any'));
    }

    // ─── resolveEffectiveOverrides ──────────────────────────

    #[Test]
    public function it_should_map_override_dtos_to_allow_deny_status_array(): void
    {
        // Arrange
        $group = new OverrideGroupDTO('role', 'Admin', 'some-uuid', ['SEND_MESSAGES' => true]);
        $collection = new ChannelOverridesCollection(['role:some-uuid' => $group]);

        // Act
        $result = $this->service->resolveEffectiveOverrides('role', 'some-uuid', $collection);

        // Assert
        $this->assertSame(['SEND_MESSAGES' => 'allow'], $result);
    }

    #[Test]
    public function it_should_return_empty_array_when_target_has_no_overrides_in_collection(): void
    {
        // Arrange
        $collection = new ChannelOverridesCollection([]);

        // Act
        $result = $this->service->resolveEffectiveOverrides('role', 'missing-uuid', $collection);

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function it_should_correctly_map_false_values_to_deny_status(): void
    {
        // Arrange
        $group = new OverrideGroupDTO('member', 'User', 'id', ['SEND_MESSAGES' => false]);
        $collection = new ChannelOverridesCollection(['member:id' => $group]);

        // Act
        $result = $this->service->resolveEffectiveOverrides('member', 'id', $collection);

        // Assert
        $this->assertSame(['SEND_MESSAGES' => 'deny'], $result);
    }

    // ─── saveTargetOverrides ──────────

    #[Test]
    public function it_should_set_allow_to_true_when_saving_allow_state(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);

        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);
        $this->permissionRepo->method('findByNames')->willReturn([$permission]);

        $savedOverride = null;
        $this->channelOverrideRepo
            ->expects($this->once())
            ->method('add')
            ->willReturnCallback(function (ChannelOverride $override) use (&$savedOverride) {
                $savedOverride = $override;
            });

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'allow'],
        );

        // Assert
        $this->assertNotNull($savedOverride);
        $this->assertTrue($savedOverride->isAllow());
    }

    #[Test]
    public function it_should_set_allow_to_false_when_saving_deny_state(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);

        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn(['SEND_MESSAGES']);
        $this->permissionRepo->method('findByNames')->willReturn([$permission]);

        $savedOverride = null;
        $this->channelOverrideRepo
            ->expects($this->once())
            ->method('add')
            ->willReturnCallback(function (ChannelOverride $override) use (&$savedOverride) {
                $savedOverride = $override;
            });

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'deny'],
        );

        // Assert
        $this->assertNotNull($savedOverride);
        $this->assertFalse($savedOverride->isAllow());
    }

    #[Test]
    public function it_should_clear_existing_target_overrides_before_saving_new_ones(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $roleId = $role->getId()->toRfc4122();

        $this->userRoleRepo->method('find')->willReturn($role);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);

        $this->channelOverrideRepo
            ->expects($this->once())
            ->method('deleteForTarget')
            ->with($this->channel, 'role', $roleId);

        // Act
        $this->service->saveTargetOverrides($this->channel, 'role', $roleId, []);

        // Assert is implicit
    }

    #[Test]
    public function it_should_persist_multiple_valid_overrides_in_a_single_call(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $permission1 = new Permission(UserPermissionEnum::SEND_MESSAGES);
        $permission2 = new Permission(UserPermissionEnum::MANAGE_CHANNELS);

        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);
        $this->permissionRepo->method('findByNames')->willReturn([$permission1, $permission2]);

        $savedOverrides = [];
        $this->channelOverrideRepo
            ->expects($this->exactly(2))
            ->method('add')
            ->willReturnCallback(function (ChannelOverride $override) use (&$savedOverrides) {
                $savedOverrides[] = $override;
            });

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'allow', 'MANAGE_CHANNELS' => 'allow'],
        );

        // Assert
        $this->assertCount(2, $savedOverrides);

        /** @var array<int, ChannelOverride> $savedOverrides */
        $override1 = $savedOverrides[0];
        $override2 = $savedOverrides[1];

        $this->assertTrue($override1->isAllow());
        $this->assertTrue($override2->isAllow());
    }

    #[Test]
    public function it_should_persist_valid_overrides_and_ignore_invalid_ones_in_the_same_payload(): void
    {
        // Arrange
        $role = new UserRole('Moderator', 2, $this->server);
        $permission = new Permission(UserPermissionEnum::SEND_MESSAGES);

        $this->userRoleRepo->method('find')->willReturn($role);
        $this->channelOverrideRepo->method('findByChannel')->willReturn([]);
        $this->rolePermissionRepo->method('findPermissionNamesByRoleIds')->willReturn([]);
        $this->permissionRepo->method('findByNames')->willReturn([$permission]);

        $savedOverride = null;
        $this->channelOverrideRepo->expects($this->once())
            ->method('add')
            ->willReturnCallback(function (ChannelOverride $override) use (&$savedOverride) {
                $savedOverride = $override;
            });

        // Act
        $this->service->saveTargetOverrides(
            $this->channel,
            'role',
            $role->getId()->toRfc4122(),
            ['SEND_MESSAGES' => 'allow', 'MANAGE_CHANNELS' => 'banana', 'VIEW_CHANNEL' => 'inherit'],
        );

        // Assert
        $this->assertNotNull($savedOverride);
        $this->assertTrue($savedOverride->isAllow());
    }
}
