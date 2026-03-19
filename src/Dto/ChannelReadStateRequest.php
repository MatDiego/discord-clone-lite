<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ChannelReadStateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $last_read_message_id = '',
    ) {
    }
}
