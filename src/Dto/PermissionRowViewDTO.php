<?php

declare(strict_types=1);

namespace App\Dto;

use App\Enum\UserPermissionEnum;

final readonly class PermissionRowViewDTO
{
    public function __construct(
        public UserPermissionEnum $permission,
        public ?string $inheritedStatus,
        public ?string $currentStatus,
    ) {}

    public function getLabel(): string
    {
        return $this->permission->trans();
    }

    public function getEffectiveState(): string
    {
        return $this->currentStatus ?? ($this->inheritedStatus === 'allow' ? 'allow' : 'none');
    }

    public function getBaseIcon(): string
    {
        return $this->inheritedStatus === 'allow' ? 'bi:check-circle-fill' : 'bi:x-circle-fill';
    }

    public function getBaseBadgeClass(): string
    {
        return $this->inheritedStatus === 'allow' ? 'perm-base-badge--allow' : 'perm-base-badge--deny';
    }
}
