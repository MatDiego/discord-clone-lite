<?php

namespace App\Factory;

use App\Entity\Channel;
use App\Enum\ChannelTypeEnum;
use DateTimeImmutable;
use Override;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Channel>
 */
final class ChannelFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    #[Override]
    public static function class(): string
    {
        return Channel::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->text(255),
            'server' => ServerFactory::new(),
            'type' => self::faker()->randomElement(ChannelTypeEnum::cases()),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year', 'now')),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
