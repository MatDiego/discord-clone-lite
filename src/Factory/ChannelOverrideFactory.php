<?php

namespace App\Factory;

use App\Entity\ChannelOverride;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<ChannelOverride>
 */
final class ChannelOverrideFactory extends PersistentProxyObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return ChannelOverride::class;
    }

    #[\Override]
    protected function defaults(): array
    {
        return [
            'allow' => self::faker()->boolean(),
            'channel' => ChannelFactory::new(),
            'permission' => PermissionFactory::new(),
            'role' => null,
            'serverMember' => null,
        ];
    }
}
