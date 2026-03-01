<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\RolePermission;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<RolePermission>
 */
final class RolePermissionFactory extends PersistentProxyObjectFactory
{
    public function __construct()
    {
        parent::__construct();
    }

    #[\Override]
    public static function class(): string
    {
        return RolePermission::class;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'permission' => PermissionFactory::new(),
            'role' => UserRoleFactory::new(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
