<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\UpdateProfileRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ProfileService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function updateProfile(User $user, UpdateProfileRequest $dto): void
    {
        $user->setUsername($dto->username);
        $user->setEmail($dto->email);

        if ($dto->plainPassword !== null && $dto->plainPassword !== '') {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->plainPassword));
        }

        $this->userRepository->flush();
    }
}
