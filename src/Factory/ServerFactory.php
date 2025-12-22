<?php

namespace App\Factory;

use App\Entity\Server;
use App\Enum\ChannelType;
use Override;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Server>
 */
final class ServerFactory extends PersistentProxyObjectFactory
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
        return Server::class;
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
            'name' => self::faker()->company(),
            'owner' => UserFactory::new(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    #[Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function(Server $server): void {
                ChannelFactory::createOne([
                    'name' => 'ogólny',
                    'type' => ChannelType::TEXT,
                    'server' => $server,
                ]);
            });
    }
}
