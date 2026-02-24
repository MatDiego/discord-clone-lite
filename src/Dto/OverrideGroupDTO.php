<?php

namespace App\Dto;

/**
 * Represents a group of channel overrides for a single target (role or member).
 */
class OverrideGroupDTO
{
    /**
     * @param string $type 'role' or 'member'
     * @param string $label Display name
     * @param string $id UUID of the target
     * @param array<string, bool> $overrides Permission name => allow (true/false)
     */
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly string $id,
        public array $overrides = [],
    ) {
    }

    public function getKey(): string
    {
        return $this->type . ':' . $this->id;
    }

    public function getOverrideCount(): int
    {
        return count($this->overrides);
    }

    public function addPermission(string $name, bool $isAllow): void
    {
        $this->overrides[$name] = $isAllow;
    }
}
