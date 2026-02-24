<?php

namespace App\Factory;

use App\Entity\UserRole;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<UserRole>
 */
final class UserRoleFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return UserRole::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
            'position' => self::faker()->randomDigitNotZero(),
            'server' => ServerFactory::new(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
