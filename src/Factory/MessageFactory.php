<?php

namespace App\Factory;

use App\Entity\Message;
use DateTimeImmutable;
use Override;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

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
            'author' => UserFactory::new(),
            'channel' => ChannelFactory::new(),
            'content' => self::faker()->realText(rand(20, 200)),
            'createdAt' => DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year', 'now')),
        ];
    }

    #[Override]
    protected function initialize(): static
    {
        return $this;
    }
}
