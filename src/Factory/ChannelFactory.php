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
    private static ?DateTimeImmutable $lastDate = null;

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
        if (self::$lastDate === null) {
            self::$lastDate = new DateTimeImmutable('-1 year');
        }

        $minutesToAdd = rand(1, 120);
        $currentDate = self::$lastDate->modify("+{$minutesToAdd} minutes");
        self::$lastDate = $currentDate;

        $channelNames = ['ogólny', 'pomoc', 'off-topic', 'newsy', 'pytania', 'media', 'linki', 'memy', 'muzyka', 'gry'];

        return [
            'name' => self::faker()->randomElement($channelNames) . '-' . self::faker()->numberBetween(1, 99),
            'server' => ServerFactory::new(),
            'type' => self::faker()->randomElement(ChannelTypeEnum::cases()),
            'createdAt' => $currentDate,
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
