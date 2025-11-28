<?php

namespace App\DataFixtures;

use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne([
            'email' => 'admin@discord.test',
            'username' => 'Admin',
            'password' => 'admin123',
            'roles' => ['ROLE_ADMIN']
        ]);

        UserFactory::createMany(10);
        $manager->flush();
    }
}
