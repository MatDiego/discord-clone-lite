<?php

namespace App\DataFixtures;

use App\Entity\ServerMember;
use App\Factory\ServerFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

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
        $manager->flush();
    }
}
