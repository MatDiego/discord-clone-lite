<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    public string $username = '';

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\Length(min: 6, max: 4096)]
    public ?string $plainPassword = null;

    public static function fromUser(User $user): self
    {
        $dto = new self();
        $dto->username = $user->getUsername();
        $dto->email = $user->getEmail();

        return $dto;
    }
}
