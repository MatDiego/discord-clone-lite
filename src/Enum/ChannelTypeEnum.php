<?php

declare(strict_types=1);

namespace App\Enum;

use Override;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ChannelTypeEnum: string implements TranslatableInterface
{
    case TEXT = 'TEXT';
    case VOICE = 'VOICE';

    #[Override]
    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(match ($this) {
            self::TEXT => 'channel.type.text',
            self::VOICE => 'channel.type.voice',
        }, locale: $locale);
    }
}
