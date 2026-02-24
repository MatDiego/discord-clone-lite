<?php

namespace App\Dto;

use App\Enum\UserPermissionEnum;

class PermissionRowViewDTO
{
    public function __construct(
        public readonly UserPermissionEnum $permission,
        public readonly ?string $inheritedStatus,
        public readonly ?string $currentStatus,
    ) {}

    public function getLabel(): string
    {
        return $this->permission->value;
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
