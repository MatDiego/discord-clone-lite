<?php

namespace App\Dto;

use App\Enum\ChannelTypeEnum;
use Symfony\Component\Validator\Constraints as Assert;

class CreateChannelRequest
{
    #[Assert\NotBlank(message: 'channel.name.not_blank')]
    #[Assert\Length(min: 1, max: 25, minMessage: 'channel.name.min_length', maxMessage: 'channel.name.max_length')]
    public string $name = '';

    #[Assert\NotNull]
    public ChannelTypeEnum $type = ChannelTypeEnum::TEXT;
}
