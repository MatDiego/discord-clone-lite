<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Message;
use DateTimeImmutable;
use Override;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use function Zenstruck\Foundry\lazy;

/**
 * @extends PersistentProxyObjectFactory<Message>
 */
final class MessageFactory extends PersistentProxyObjectFactory
{
    private static ?DateTimeImmutable $lastDate = null;
    public function __construct()
    {
        parent::__construct();
    }

    #[Override]
    public static function class(): string
    {
        return Message::class;
    }

    #[Override]
    protected function defaults(): array|callable
    {
        if (self::$lastDate === null) {
            self::$lastDate = new DateTimeImmutable('-1 month');
        }

        $minutesToAdd = rand(1, 120);
        $currentDate = self::$lastDate->modify("+{$minutesToAdd} minutes");

        self::$lastDate = $currentDate;

        return [
            'content' => self::faker()->realText(rand(20, 200)),
            'createdAt' => $currentDate,
            'channel' => lazy(fn() => ChannelFactory::random()),
            'author' => lazy(fn() => UserFactory::random()),
        ];
    }

    /** @psalm-suppress MoreSpecificReturnType, LessSpecificReturnStatement */
    #[Override]
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (Message $message): void {
            $reflectionClass = new \ReflectionClass($message);
            $property = $reflectionClass->getProperty('id');

            $uuid = Uuid::v7();

            $property->setValue($message, $uuid);
        });
    }
}
