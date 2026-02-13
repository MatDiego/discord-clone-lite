<?php

namespace App\Factory;

use App\Entity\Message;
use DateTimeImmutable;
use Override;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use function Zenstruck\Foundry\lazy;

/**
 * @extends PersistentProxyObjectFactory<Message>
 */
final class MessageFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
    }

    #[Override]
    public static function class(): string
    {
        return Message::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        return [
            'content' => self::faker()->realText(rand(20, 200)),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 month', 'now')),
            'channel' => lazy(fn() => ChannelFactory::random()),
            'author' => lazy(fn() => UserFactory::random()),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
