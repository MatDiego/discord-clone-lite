<?php

declare(strict_types=1);

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
        parent::__construct();
    }

    #[Override]
    public static function class(): string
    {
        return Channel::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        $channelNames = ['ogólny', 'pomoc', 'off-topic', 'newsy', 'pytania', 'media', 'linki', 'memy', 'muzyka', 'gry'];

        return [
            'name' => self::faker()->randomElement($channelNames) . '-' . self::faker()->numberBetween(1, 99),
            'server' => ServerFactory::new(),
            'type' => self::faker()->randomElement(ChannelTypeEnum::cases()),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year')),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
