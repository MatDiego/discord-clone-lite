<?php

namespace App\DataFixtures;

use App\Entity\ServerMember;
use App\Factory\ChannelFactory;
use App\Factory\MessageFactory;
use App\Factory\ServerFactory;
use App\Factory\UserFactory;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use function Zenstruck\Foundry\faker;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $admin = UserFactory::createOne([
            'email' => 'admin@discord.test',
            'username' => 'Admin',
            'password' => 'admin123',
            'roles' => ['ROLE_ADMIN']
        ]);

        $servers = ServerFactory::createMany(3, ['owner' => $admin]);
        foreach ($servers as $server) {
            $member = new ServerMember();
            $member->setUser($admin->_real());
            $member->setServer($server->_real());
            $manager->persist($member);
        }

        UserFactory::createMany(10);

        MessageFactory::createMany(30, function() {
            return [
                'channel' => ChannelFactory::random(),
                'author' => UserFactory::random(),
                'createdAt' => DateTimeImmutable::createFromMutable(faker()->dateTimeBetween('-1 month')),
            ];
        });
        $manager->flush();
    }
}
