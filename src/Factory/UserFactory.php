<?php

namespace App\Factory;

use App\Entity\User;
use Override;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    #[Override]
    public static function class(): string
    {
        return User::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->text(180),
            'password' => self::faker()->text(),
            'roles' => [],
            'username' => self::faker()->text(50),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this
             ->afterInstantiate(function(User $user): void {
                 $plainPassword = $user->getPassword();
                 $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                 $user->setPassword($hashedPassword);
             })
        ;
    }
}
