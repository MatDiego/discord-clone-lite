<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Enum\UserPermissionEnum;
use App\Factory\ChannelFactory;
use App\Factory\ChannelOverrideFactory;
use App\Factory\PermissionFactory;
use App\Factory\ServerFactory;
use App\Factory\ServerMemberFactory;
use App\Factory\UserFactory;
use App\Factory\UserRoleFactory;
use App\Repository\ChannelOverrideRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

final class ChannelOverrideRepositoryTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    private ChannelOverrideRepository $repository;
    private EntityManagerInterface $entityManager;

    #[Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(ChannelOverrideRepository::class);
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    // --- findApplicableOverrides ---

    public function test_it_should_find_overrides_for_member_when_role_ids_array_is_empty(): void
    {
        // Arrange
        $server = ServerFactory::createOne();
        $targetChannel = ChannelFactory::createOne(['server' => $server]);
        $otherChannel = ChannelFactory::createOne(['server' => $server]);

        $sendMessagesPermission = PermissionFactory::createOne(['name' => UserPermissionEnum::SEND_MESSAGES]);
        $viewChannelPermission = PermissionFactory::createOne(['name' => UserPermissionEnum::VIEW_CHANNEL]);

        $targetUser = UserFactory::createOne();
        $otherUser = UserFactory::createOne();

        $targetMember = ServerMemberFactory::createOne(['server' => $server, 'user' => $targetUser]);
        $otherMember = ServerMemberFactory::createOne(['server' => $server, 'user' => $otherUser]);

        $expectedOverride = ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'permission' => $sendMessagesPermission,
            'serverMember' => $targetMember
        ]);

        ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'permission' => $sendMessagesPermission,
            'serverMember' => $otherMember
        ]);

        ChannelOverrideFactory::createOne([
            'channel' => $otherChannel,
            'permission' => $sendMessagesPermission,
            'serverMember' => $targetMember
        ]);

        ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'permission' => $viewChannelPermission,
            'serverMember' => $targetMember
        ]);

        // Act
        $results = $this->repository->findApplicableOverrides(
            $targetChannel->_real(),
            UserPermissionEnum::SEND_MESSAGES,
            [],
            $targetMember->_real()
        );

        // Assert
        $this->assertCount(1, $results, 'Exactly 1 result should be returned.');
        $this->assertSame($expectedOverride->getId()->toRfc4122(), $results[0]->getId()->toRfc4122());
    }

    public function test_it_should_find_overrides_matching_member_or_any_of_the_provided_roles(): void
    {
        // Arrange
        $server = ServerFactory::createOne();
        $targetChannel = ChannelFactory::createOne(['server' => $server]);
        $createInvitePermission = PermissionFactory::createOne(['name' => UserPermissionEnum::CREATE_INVITE]);

        $targetMember = ServerMemberFactory::createOne(['server' => $server]);
        $otherMember = ServerMemberFactory::createOne(['server' => $server]);

        $matchingRole1 = UserRoleFactory::createOne(['server' => $server]);
        $matchingRole2 = UserRoleFactory::createOne(['server' => $server]);
        $nonMatchingRole = UserRoleFactory::createOne(['server' => $server]);

        $expectedMemberOverride = ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'permission' => $createInvitePermission,
            'serverMember' => $targetMember
        ]);

        $expectedRoleOverride1 = ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'permission' => $createInvitePermission,
            'role' => $matchingRole1
        ]);

        $expectedRoleOverride2 = ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'permission' => $createInvitePermission,
            'role' => $matchingRole2
        ]);

        ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'permission' => $createInvitePermission,
            'role' => $nonMatchingRole
        ]);

        ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'permission' => $createInvitePermission,
            'serverMember' => $otherMember
        ]);

        // Act
        $results = $this->repository->findApplicableOverrides(
            $targetChannel->_real(),
            UserPermissionEnum::CREATE_INVITE,
            [$matchingRole1->getId(), $matchingRole2->getId()],
            $targetMember->_real()
        );

        // Assert
        $this->assertCount(3, $results, 'Exactly 3 results should be returned.');
        $ids = array_map(fn($override) => $override->getId()->toRfc4122(), $results);
        $this->assertContains($expectedMemberOverride->getId()->toRfc4122(), $ids);
        $this->assertContains($expectedRoleOverride1->getId()->toRfc4122(), $ids);
        $this->assertContains($expectedRoleOverride2->getId()->toRfc4122(), $ids);
    }

    // --- findByChannel ---

    public function test_it_should_return_all_overrides_for_given_channel_and_ignore_other_channels(): void
    {
        // Arrange
        $server = ServerFactory::createOne();
        $targetChannel = ChannelFactory::createOne(['server' => $server]);
        $otherChannel = ChannelFactory::createOne(['server' => $server]);
        $viewChannelPermission = PermissionFactory::createOne(['name' => UserPermissionEnum::VIEW_CHANNEL]);
        $role = UserRoleFactory::createOne(['server' => $server]);

        ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'role' => $role, 'permission' => $viewChannelPermission]);
        ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'role' => $role, 'permission' => $viewChannelPermission]);
        ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'role' => $role, 'permission' => $viewChannelPermission]);

        ChannelOverrideFactory::createOne(['channel' => $otherChannel, 'role' => $role, 'permission' => $viewChannelPermission]);
        ChannelOverrideFactory::createOne(['channel' => $otherChannel, 'role' => $role, 'permission' => $viewChannelPermission]);

        // Act
        $results = $this->repository->findByChannel($targetChannel->_real());

        // Assert
        $this->assertCount(3, $results, 'Exactly 3 results should be returned.');
        foreach ($results as $result) {
            $this->assertSame($targetChannel->getId()->toRfc4122(), $result->getChannel()->getId()->toRfc4122());
        }
    }

    public function test_it_should_sort_overrides_by_role_name_asc_and_then_by_username_asc(): void
    {
        // Arrange
        $server = ServerFactory::createOne();
        $targetChannel = ChannelFactory::createOne(['server' => $server]);
        $viewChannelPermission = PermissionFactory::createOne(['name' => UserPermissionEnum::VIEW_CHANNEL]);

        $roleZeta = UserRoleFactory::createOne(['server' => $server, 'name' => 'Zeta']);
        $roleAlfa = UserRoleFactory::createOne(['server' => $server, 'name' => 'Alfa']);

        $userZosia = UserFactory::createOne(['username' => 'Zosia']);
        $userAdam = UserFactory::createOne(['username' => 'Adam']);
        $userBartek = UserFactory::createOne(['username' => 'Bartek']);

        $memberZosia = ServerMemberFactory::createOne(['server' => $server, 'user' => $userZosia]);
        $memberAdam = ServerMemberFactory::createOne(['server' => $server, 'user' => $userAdam]);
        $memberBartek = ServerMemberFactory::createOne(['server' => $server, 'user' => $userBartek]);

        ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'role' => $roleZeta, 'permission' => $viewChannelPermission]);
        ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'role' => $roleAlfa, 'serverMember' => $memberZosia, 'permission' => $viewChannelPermission]);
        ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'role' => $roleAlfa, 'serverMember' => $memberAdam, 'permission' => $viewChannelPermission]);
        ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'serverMember' => $memberBartek, 'permission' => $viewChannelPermission]);

        // Act
        $results = $this->repository->findByChannel($targetChannel->_real());

        // Assert
        $this->assertCount(4, $results);

        $this->assertSame('Alfa', $results[0]->getRole()?->getName());
        $this->assertSame('Adam', $results[0]->getServerMember()?->getUser()->getUsername());

        $this->assertSame('Alfa', $results[1]->getRole()?->getName());
        $this->assertSame('Zosia', $results[1]->getServerMember()?->getUser()->getUsername());

        $hasBartek = false;
        $hasZeta = false;

        foreach ([$results[2], $results[3]] as $result) {
            if ($result->getRole()?->getName() === 'Zeta')
                $hasZeta = true;
            if ($result->getServerMember()?->getUser()->getUsername() === 'Bartek')
                $hasBartek = true;
        }
        $this->assertTrue($hasZeta);
        $this->assertTrue($hasBartek);
    }

    public function test_it_should_fetch_related_entities_to_prevent_n_plus_one_problem(): void
    {
        // Arrange
        $server = ServerFactory::createOne();
        $targetChannel = ChannelFactory::createOne(['server' => $server]);
        $viewChannelPermission = PermissionFactory::createOne(['name' => UserPermissionEnum::VIEW_CHANNEL]);
        $role = UserRoleFactory::createOne(['server' => $server]);
        $user = UserFactory::createOne();
        $member = ServerMemberFactory::createOne(['server' => $server, 'user' => $user]);

        ChannelOverrideFactory::createOne([
            'channel' => $targetChannel,
            'role' => $role,
            'serverMember' => $member,
            'permission' => $viewChannelPermission
        ]);

        $this->entityManager->clear();

        // Act
        $results = $this->repository->findByChannel($targetChannel->_real());

        // Assert
        $this->assertCount(1, $results);
        $firstOverride = $results[0];

        $this->assertFalse($firstOverride->getPermission() instanceof Proxy, 'Permission was lazy loaded (Proxy) meaning N+1 occurred.');
        $this->assertFalse($firstOverride->getRole() instanceof Proxy, 'UserRole was lazy loaded (Proxy) meaning N+1 occurred.');

        $serverMember = $firstOverride->getServerMember();
        $this->assertNotNull($serverMember, 'Server member not found.');
        /** @psalm-assert !null $serverMember */

        $this->assertFalse($serverMember instanceof Proxy, 'ServerMember was lazy loaded (Proxy) meaning N+1 occurred.');
        $this->assertFalse($serverMember->getUser() instanceof Proxy, 'User was lazy loaded (Proxy) meaning N+1 occurred.');
    }

    // --- deleteForTarget ---

    public function test_it_should_delete_override_for_specific_role_in_given_channel(): void
    {
        // Arrange
        $server = ServerFactory::createOne();
        $targetChannel = ChannelFactory::createOne(['server' => $server]);
        $otherChannel = ChannelFactory::createOne(['server' => $server]);

        $targetRole = UserRoleFactory::createOne(['server' => $server]);
        $otherRole = UserRoleFactory::createOne(['server' => $server]);
        $viewChannelPermission = PermissionFactory::createOne(['name' => UserPermissionEnum::VIEW_CHANNEL]);

        $targetOverride = ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'role' => $targetRole, 'permission' => $viewChannelPermission]);
        $otherRoleOverride = ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'role' => $otherRole, 'permission' => $viewChannelPermission]);
        $otherChannelOverride = ChannelOverrideFactory::createOne(['channel' => $otherChannel, 'role' => $targetRole, 'permission' => $viewChannelPermission]);

        // Act
        $this->repository->deleteForTarget(
            $targetChannel->_real(),
            'role',
            $targetRole->getId()->toRfc4122()
        );

        // Assert
        $allOverrides = $this->repository->findAll();
        $this->assertCount(2, $allOverrides, 'Only 2 records remain in the database (Target was deleted).');

        $ids = array_map(fn($override) => $override->getId()->toRfc4122(), $allOverrides);
        $this->assertContains($otherRoleOverride->getId()->toRfc4122(), $ids);
        $this->assertContains($otherChannelOverride->getId()->toRfc4122(), $ids);
        $this->assertNotContains($targetOverride->getId()->toRfc4122(), $ids);
    }

    public function test_it_should_delete_override_for_specific_member_in_given_channel(): void
    {
        // Arrange
        $server = ServerFactory::createOne();
        $targetChannel = ChannelFactory::createOne(['server' => $server]);
        $otherChannel = ChannelFactory::createOne(['server' => $server]);

        $targetMember = ServerMemberFactory::createOne(['server' => $server]);
        $otherMember = ServerMemberFactory::createOne(['server' => $server]);
        $viewChannelPermission = PermissionFactory::createOne(['name' => UserPermissionEnum::VIEW_CHANNEL]);

        $targetOverride = ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'serverMember' => $targetMember, 'permission' => $viewChannelPermission]);
        $otherMemberOverride = ChannelOverrideFactory::createOne(['channel' => $targetChannel, 'serverMember' => $otherMember, 'permission' => $viewChannelPermission]);
        $otherChannelOverride = ChannelOverrideFactory::createOne(['channel' => $otherChannel, 'serverMember' => $targetMember, 'permission' => $viewChannelPermission]);

        // Act
        $this->repository->deleteForTarget(
            $targetChannel->_real(),
            'serverMember',
            $targetMember->getId()->toRfc4122()
        );

        // Assert
        $allOverrides = $this->repository->findAll();
        $this->assertCount(2, $allOverrides, 'Only 2 records remain in the database (Target was deleted).');

        $ids = array_map(fn($override) => $override->getId()->toRfc4122(), $allOverrides);
        $this->assertContains($otherMemberOverride->getId()->toRfc4122(), $ids);
        $this->assertContains($otherChannelOverride->getId()->toRfc4122(), $ids);
        $this->assertNotContains($targetOverride->getId()->toRfc4122(), $ids);
    }
}
