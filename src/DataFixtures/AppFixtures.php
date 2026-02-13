<?php

namespace App\DataFixtures;

use App\Enum\UserPermissionEnum;
use App\Factory\ChannelFactory;
use App\Factory\MessageFactory;
use App\Factory\PermissionFactory;
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
        foreach (UserPermissionEnum::cases() as $case) {
            PermissionFactory::createOne(['name' => $case]);
        }

        $admin = UserFactory::createOne([
            'email' => 'admin@discord.test',
            'username' => 'Admin',
            'password' => 'admin123',
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

        foreach (ServerFactory::all() as $server) {
            ChannelFactory::createMany(rand(1, 3), [
                'server' => $server,
            ]);
        }

        foreach (ChannelFactory::all() as $channel) {
            MessageFactory::createMany(rand(5, 25), [
                'channel' => $channel,
            ]);
        }

        $manager->flush();
    }
}
