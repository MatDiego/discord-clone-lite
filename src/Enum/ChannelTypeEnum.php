<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ChannelTypeEnum: string implements TranslatableInterface
{
    case TEXT = 'TEXT';
    case VOICE = 'VOICE';
    case READ_ONLY = 'READ_ONLY';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans(match ($this) {
            self::TEXT => 'channel.type.text',
            self::VOICE => 'channel.type.voice',
            self::READ_ONLY => 'channel.type.read-only',
        }, locale: $locale);
    }
}
