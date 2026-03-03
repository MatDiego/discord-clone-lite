<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Server;
use App\Enum\ChannelTypeEnum;
use Override;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Server>
 */
final class ServerFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[Override]
    public static function class(): string
    {
        return Server::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->company(),
            'owner' => UserFactory::new(),
        ];
    }


    /**
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType
     */
    #[Override]
    protected function initialize(): static
    {
        return $this
            ->afterInstantiate(function (Server $server): void {
                ChannelFactory::createOne([
                    'name' => 'ogólny',
                    'type' => ChannelTypeEnum::TEXT,
                    'server' => $server,
                ]);

                ServerMemberFactory::findOrCreate([
                    'user' => $server->getOwner(),
                    'server' => $server,
                ]);
            });
    }
}
