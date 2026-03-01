<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class ChannelOverridesCollection
{
    /** @param array<string, OverrideGroupDTO> $groups */
    public function __construct(
        private array $groups = []
    ) {}

    public function getAll(): array
    {
        return $this->groups;
    }

    public function get(string $key): ?OverrideGroupDTO
    {
        return $this->groups[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->groups[$key]);
    }
    public function getRoles(): array
    {
        return array_filter($this->groups, fn(OverrideGroupDTO $g) => $g->type === 'role');
    }

    public function getMembers(): array
    {
        return array_filter($this->groups, fn(OverrideGroupDTO $g) => $g->type === 'member');
    }
}
