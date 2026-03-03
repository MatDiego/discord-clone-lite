<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
final class RegistrationRequest
{
    #[Assert\NotBlank(message: 'register.email.not_blank')]
    #[Assert\Email(message: 'register.email.invalid')]
    #[Assert\Length(max: 180, maxMessage: 'register.email.max_length')]
    public string $email = '';

    #[Assert\NotBlank(message: 'register.username.not_blank')]
    #[Assert\Length(min: 3, max: 50, minMessage: 'register.username.min_length', maxMessage: 'register.username.max_length')]
    public string $username = '';

    #[Assert\NotBlank(message: 'register.password.not_blank')]
    #[Assert\Length(min: 6, max: 30, minMessage: 'register.password.min_length', maxMessage: 'register.password.max_length')]
    public string $plainPassword = '';

    #[Assert\IsTrue(message: 'register.agree_terms.is_true')]
    public bool $agreeTerms = false;
}
