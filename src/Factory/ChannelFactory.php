<?php

namespace App\Factory;

use App\Entity\Channel;
use App\Enum\ChannelType;
use DateTimeImmutable;
use Override;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Channel>
 */
final class ChannelFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    #[Override]
    public static function class(): string
    {
        return Channel::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->text(255),
            'server' => ServerFactory::new(),
            'type' => self::faker()->randomElement(ChannelType::cases()),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year', 'now')),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Channel $channel): void {})
        ;
    }
}
