<?php

declare(strict_types=1);

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
        private readonly UserPasswordHasherInterface $passwordHasher
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
            'email' => self::faker()->unique()->email(),
            'password' => 'pass123',
            'roles' => ['ROLE_USER'],
            'username' => self::faker()->userName(),
            'isVerified' => true,
        ];
    }

    /**
     * @psalm-suppress MoreSpecificReturnType, LessSpecificReturnStatement
     */
    #[Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (User $user): void {
                $plainPassword = $user->getPassword();
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            })
        ;
    }
}
