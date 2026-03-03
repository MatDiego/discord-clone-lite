<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\MemberRole;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<MemberRole>
 */
final class MemberRoleFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    public static function class(): string
    {
        return MemberRole::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'role' => UserRoleFactory::new(),
            'serverMember' => ServerMemberFactory::new(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
