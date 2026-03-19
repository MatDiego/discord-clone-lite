<?php

declare(strict_types=1);

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
use Override;

final class AppFixtures extends Fixture
{
    private const array SERVER_CHANNELS = [
        'Programiści' => ['pomoc', 'projekty', 'code-review', 'off-topic'],
        'Gaming' => ['gry', 'memy', 'lfg', 'off-topic'],
        'Muzyka' => ['rekomendacje', 'koncerty', 'off-topic'],
        'Nauka' => ['matematyka', 'fizyka', 'pomoc', 'off-topic'],
        'Sztuka i Design' => ['portfolio', 'inspiracje', 'feedback', 'off-topic'],
    ];

    private const array MEMBER_PERMISSIONS = [
        UserPermissionEnum::VIEW_CHANNELS,
        UserPermissionEnum::SEND_MESSAGES,
        UserPermissionEnum::CREATE_INVITE,
        UserPermissionEnum::VIEW_CHANNEL,
    ];

    private const array MODERATOR_PERMISSIONS = [
        UserPermissionEnum::KICK_MEMBERS,
        UserPermissionEnum::BAN_MEMBERS,
        UserPermissionEnum::MANAGE_NICKNAMES,
        UserPermissionEnum::MANAGE_MESSAGES,
        UserPermissionEnum::VIEW_CHANNELS,
        UserPermissionEnum::SEND_MESSAGES,
        UserPermissionEnum::CREATE_INVITE,
        UserPermissionEnum::VIEW_CHANNEL,
    ];

    #[Override]
    public function load(ObjectManager $manager): void
    {
        $permissions = $this->createPermissions();

        $demoAdmin = UserFactory::createOne([
            'email' => 'admin@demo.test',
            'username' => 'Admin',
            'password' => 'admin123',
        ]);

        $demoMember = UserFactory::createOne([
            'email' => 'member@demo.test',
            'username' => 'Member',
            'password' => 'member123',
        ]);

        $fillerUsers = UserFactory::createMany(20);

        $servers = $this->createServers($demoAdmin);

        $this->assignRolesAndMembers($servers, $demoAdmin, $demoMember, $fillerUsers, $permissions);
        $this->createChannelsForServers($servers);
        $this->createMessagesForServers();

        $manager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function createPermissions(): array
    {
        $permissions = [];
        foreach (UserPermissionEnum::cases() as $case) {
            $permissions[$case->value] = PermissionFactory::createOne(['name' => $case]);
        }

        return $permissions;
    }

    /**
     * @return list<mixed>
     */
    private function createServers(mixed $owner): array
    {
        $servers = [];
        foreach (array_keys(self::SERVER_CHANNELS) as $name) {
            $servers[] = ServerFactory::createOne([
                'name' => $name,
                'owner' => $owner,
            ]);
        }

        return $servers;
    }

    private function assignRolesAndMembers(
        array $servers,
        mixed $demoAdmin,
        mixed $demoMember,
        array $fillerUsers,
        array $permissions,
    ): void {
        foreach ($servers as $server) {
            $realServer = $server->_real();

            $adminRole = $this->createRoleWithPermissions(
                'Admin',
                1,
                $realServer,
                $permissions,
                UserPermissionEnum::cases(),
            );

            $modRole = $this->createRoleWithPermissions(
                'Moderator',
                2,
                $realServer,
                $permissions,
                self::MODERATOR_PERMISSIONS,
            );

            $memberRole = $this->createRoleWithPermissions(
                'Członek',
                3,
                $realServer,
                $permissions,
                self::MEMBER_PERMISSIONS,
            );

            $adminMember = ServerMemberFactory::findOrCreate([
                'user' => $demoAdmin,
                'server' => $realServer,
            ]);
            MemberRoleFactory::createOne(['serverMember' => $adminMember, 'role' => $adminRole]);
            MemberRoleFactory::createOne(['serverMember' => $adminMember, 'role' => $memberRole]);

            $memberShip = ServerMemberFactory::createOne([
                'user' => $demoMember,
                'server' => $realServer,
            ]);
            MemberRoleFactory::createOne(['serverMember' => $memberShip, 'role' => $memberRole]);

            foreach ($fillerUsers as $user) {
                if (rand(1, 100) > 70) {
                    continue;
                }

                $membership = ServerMemberFactory::createOne([
                    'user' => $user,
                    'server' => $realServer,
                ]);
                MemberRoleFactory::createOne(['serverMember' => $membership, 'role' => $memberRole]);

                if (rand(1, 100) <= 15) {
                    MemberRoleFactory::createOne(['serverMember' => $membership, 'role' => $modRole]);
                }
            }
        }
    }

    private function createChannelsForServers(array $servers): void
    {
        $serverNames = array_keys(self::SERVER_CHANNELS);

        foreach ($servers as $index => $server) {
            $channelNames = self::SERVER_CHANNELS[$serverNames[$index]];

            foreach ($channelNames as $channelName) {
                ChannelFactory::createOne([
                    'name' => $channelName,
                    'server' => $server,
                ]);
            }
        }
    }

    private function createMessagesForServers(): void
    {
        foreach (ChannelFactory::all() as $channel) {
            $realChannel = $channel->_real();
            $server = $realChannel->getServer();

            $members = ServerMemberFactory::findBy(['server' => $server]);
            if (count($members) === 0) {
                continue;
            }

            $memberUsers = array_map(
                fn ($m) => $m->getUser(),
                $members,
            );

            MessageFactory::createMany(rand(30, 80), fn () => [
                'channel' => $channel,
                'author' => $memberUsers[array_rand($memberUsers)],
            ]);
        }
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
