<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class AddOverrideRequest
{
    #[Assert\NotBlank(message: 'override.target_type.not_blank')]
    #[Assert\Choice(choices: ['role', 'member'], message: 'override.target_type.invalid')]
    public string $targetType = '';

    #[Assert\NotBlank(message: 'override.target_id.not_blank')]
    public string $targetId = '';
}
