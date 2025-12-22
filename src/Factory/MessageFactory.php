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
        return Message::class;
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
            'author' => UserFactory::new(),
            'channel' => ChannelFactory::new(),
            'content' => self::faker()->realText(rand(20, 200)),
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
            // ->afterInstantiate(function(Message $message): void {})
        ;
    }
}
