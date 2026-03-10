<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\UpdateProfileRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ProfileService;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ProfileServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface|MockObject $passwordHasher;
    private ProfileService $service;

    #[Override]
    protected function setUp(): void
    {
        $this->userRepository = new class () extends UserRepository {
            public bool $flushed = false;
            public function __construct() {}
            public function flush(): void
            {
                $this->flushed = true;
            }
        };

        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->service = new ProfileService($this->userRepository, $this->passwordHasher);
    }

    #[Test]
    public function it_should_update_user_basic_data_and_save_state_when_password_is_empty(): void
    {
        // Arrange
        $user = new User('old@example.com', 'OldUsername', 'old_hashed_password');

        $dto = new UpdateProfileRequest();
        $dto->username = 'NewUsername';
        $dto->email = 'new@example.com';
        $dto->plainPassword = null;

        // Act
        $this->service->updateProfile($user, $dto);

        // Assert
        $this->assertSame('NewUsername', $user->getUsername());
        $this->assertSame('new@example.com', $user->getEmail());
        $this->assertSame('old_hashed_password', $user->getPassword());
        $this->assertTrue($this->userRepository->flushed);
    }

    #[Test]
    public function it_should_update_user_data_and_hash_new_password_when_password_is_provided(): void
    {
        // Arrange
        $user = new User('old@example.com', 'OldUsername', 'old_hashed_password');

        $dto = new UpdateProfileRequest();
        $dto->username = 'NewUsername';
        $dto->email = 'new@example.com';
        $dto->plainPassword = 'new_secret_password';

        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('new_hashed_password');

        // Act
        $this->service->updateProfile($user, $dto);

        // Assert
        $this->assertSame('NewUsername', $user->getUsername());
        $this->assertSame('new@example.com', $user->getEmail());
        $this->assertSame('new_hashed_password', $user->getPassword());
        $this->assertTrue($this->userRepository->flushed);
    }
}
