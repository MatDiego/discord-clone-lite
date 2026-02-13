<?php

namespace App\Factory;

use App\Entity\ServerMember;
use DateTimeImmutable;
use Override;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ServerMember>
 */
final class ServerMemberFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    #[Override]
    public static function class(): string
    {
        return ServerMember::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'server' => ServerFactory::new(),
            'joinedAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year', 'now')),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
