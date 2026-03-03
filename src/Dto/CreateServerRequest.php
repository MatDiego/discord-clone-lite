<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateServerRequest
{
    #[Assert\NotBlank(message: 'server.name.not_blank')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'server.name.min_length', maxMessage: 'server.name.max_length')]
    public string $name = '';
}
