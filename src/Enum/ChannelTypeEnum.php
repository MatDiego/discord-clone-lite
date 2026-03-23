<?php

declare(strict_types=1);

namespace App\Enum;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ChannelTypeEnum: string implements TranslatableInterface
{
    case TEXT = 'TEXT';

    #[Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(match ($this) {
            self::TEXT => 'channel.type.text',
        }, locale: $locale);
    }
}
