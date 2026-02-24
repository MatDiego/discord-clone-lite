<?php

namespace App\DataFixtures;

use App\Enum\UserPermissionEnum;
use App\Factory\ChannelFactory;
use App\Factory\MemberRoleFactory;
use App\Factory\MessageFactory;
use App\Factory\PermissionFactory;
use App\Factory\RolePermissionFactory;
use App\Factory\ServerFactory;
use App\Factory\ServerMemberFactory;
use App\Factory\UserFactory;
use App\Factory\UserRoleFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $permissions = [];
        foreach (UserPermissionEnum::cases() as $case) {
            $permissions[$case->value] = PermissionFactory::createOne(['name' => $case]);
        }

        $admin = UserFactory::createOne([
            'email' => 'admin@discord.test',
            'username' => 'Admin',
            'password' => 'admin123',
        ]);

        $testUser = UserFactory::createOne([
            'email' => 'user@discord.test',
            'username' => 'TestUser',
            'password' => 'user123',
        ]);

        $testMod = UserFactory::createOne([
            'email' => 'mod@discord.test',
            'username' => 'Moderator',
            'password' => 'mod123',
        ]);

        ServerFactory::createMany(5, ['owner' => $admin]);
        $regularUsers = UserFactory::createMany(15);

        foreach ($regularUsers as $user) {
            $serversToJoin = ServerFactory::randomRange(1, 3);
            foreach ($serversToJoin as $server) {
                ServerMemberFactory::createOne([
                    'user' => $user,
                    'server' => $server,
                ]);
            }
        }

        foreach (ServerFactory::all() as $serverProxy) {
            $server = $serverProxy->_real();

            // Create admin as member (owner)
            $adminMember = ServerMemberFactory::findOrCreate([
                'user' => $admin,
                'server' => $server,
            ]);


            $adminRole = $this->createRoleWithPermissions(
                'Admin',
                1,
                $server,
                $permissions,
                UserPermissionEnum::cases(),
            );

            $modRole = $this->createRoleWithPermissions(
                'Moderator',
                2,
                $server,
                $permissions,
                [
                    UserPermissionEnum::KICK_MEMBERS,
                    UserPermissionEnum::BAN_MEMBERS,
                    UserPermissionEnum::MANAGE_NICKNAMES,
                    UserPermissionEnum::MANAGE_MESSAGES,
                    UserPermissionEnum::VIEW_CHANNELS,
                    UserPermissionEnum::SEND_MESSAGES,
                    UserPermissionEnum::CREATE_INVITE,
                    UserPermissionEnum::VIEW_CHANNEL,
                ],
            );

            $memberRole = $this->createRoleWithPermissions(
                'Członek',
                3,
                $server,
                $permissions,
                [
                    UserPermissionEnum::VIEW_CHANNELS,
                    UserPermissionEnum::SEND_MESSAGES,
                    UserPermissionEnum::CREATE_INVITE,
                    UserPermissionEnum::VIEW_CHANNEL,
                ],
            );

            MemberRoleFactory::createOne(['serverMember' => $adminMember, 'role' => $adminRole]);
            MemberRoleFactory::createOne(['serverMember' => $adminMember, 'role' => $memberRole]);

            $testUserMember = ServerMemberFactory::createOne([
                'user' => $testUser,
                'server' => $server,
            ]);
            MemberRoleFactory::createOne(['serverMember' => $testUserMember, 'role' => $memberRole]);

            $testModMember = ServerMemberFactory::createOne([
                'user' => $testMod,
                'server' => $server,
            ]);
            MemberRoleFactory::createOne(['serverMember' => $testModMember, 'role' => $modRole]);
            MemberRoleFactory::createOne(['serverMember' => $testModMember, 'role' => $memberRole]);

            $regularMembers = ServerMemberFactory::findBy(['server' => $server]);
            foreach ($regularMembers as $member) {
                $userId = $member->getUser()->getId();
                if (
                    $userId->equals($admin->getId())
                    || $userId->equals($testUser->getId())
                    || $userId->equals($testMod->getId())
                ) {
                    continue;
                }

                MemberRoleFactory::createOne(['serverMember' => $member, 'role' => $memberRole]);

                if (rand(1, 4) === 1) {
                    MemberRoleFactory::createOne(['serverMember' => $member, 'role' => $modRole]);
                }
            }
        }

        foreach (ServerFactory::all() as $server) {
            ChannelFactory::createMany(rand(1, 3), ['server' => $server]);
        }

        foreach (ChannelFactory::all() as $channel) {
            MessageFactory::createMany(rand(5, 25), ['channel' => $channel]);
        }

        $manager->flush();
    }

    private function createRoleWithPermissions(
        string $name,
        int $position,
        mixed $server,
        array $permissions,
        array $permEnums,
    ): mixed {
        $role = UserRoleFactory::createOne([
            'name' => $name,
            'position' => $position,
            'server' => $server,
        ]);

        foreach ($permEnums as $perm) {
            RolePermissionFactory::createOne([
                'role' => $role,
                'permission' => $permissions[$perm->value],
            ]);
        }

        return $role;
    }
}
