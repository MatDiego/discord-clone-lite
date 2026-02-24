<?php

namespace App\Factory;

use App\Entity\ServerMember;
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
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
