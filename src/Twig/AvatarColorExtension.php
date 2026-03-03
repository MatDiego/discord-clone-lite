<?php

declare(strict_types=1);

namespace App\Twig;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AvatarColorExtension extends AbstractExtension
{
    private const COLORS = ['blue', 'green', 'red', 'purple', 'orange'];

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('avatar_color', [$this, 'getAvatarColor']),
        ];
    }

    /**
     * Deterministic color based on username length.
     */
    public function getAvatarColor(string $username): string
    {
        return self::COLORS[mb_strlen($username) % count(self::COLORS)];
    }
}
