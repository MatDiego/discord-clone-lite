<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\RegistrationRequest;
use App\Entity\User;
use App\Security\EmailVerifier;
use App\Repository\UserRepository;
use App\Service\RegistrationService;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationServiceTest extends TestCase
{
    private UserRepository $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private EmailVerifier $emailVerifier;
    private RegistrationService $service;
    private RegistrationRequest $dto;
    private User $user;

    #[Override]
    protected function setUp(): void
    {
        $this->userRepository = new class () extends UserRepository {
            public array $users = [];
            public bool $flushed = false;
            public function __construct()
            {}
            public function add(User $user): void
            {
                $this->users[] = $user;
            }
            public function flush(): void
            {
                $this->flushed = true;
            }
        };
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->emailVerifier = $this->createMock(EmailVerifier::class);

        $this->service = new RegistrationService(
            $this->userRepository,
            $this->passwordHasher,
            $this->emailVerifier
        );

        $this->dto = new RegistrationRequest();
        $this->dto->email = 'test@example.com';
        $this->dto->username = 'TestUser';
        $this->dto->plainPassword = 'SecretPassword123';
        $this->user = new User('test@example.com', 'TestUser', 'password');
    }


    #[Test]
    public function it_should_persist_new_user_during_registration(): void
    {
        // Arrange
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_secret_password');

        // Act
        $this->service->register($this->dto);

        // Assert
        $this->assertCount(1, $this->userRepository->users);
        $savedUser = $this->userRepository->users[0];

        $this->assertSame($this->dto->email, $savedUser->getEmail());
        $this->assertSame($this->dto->username, $savedUser->getUsername());
        $this->assertSame('hashed_secret_password', $savedUser->getPassword());
        $this->assertTrue($this->userRepository->flushed);
    }

    #[Test]
    public function it_should_send_verification_email_during_registration(): void
    {
        // Arrange
        $this->emailVerifier
            ->expects($this->once())
            ->method('sendEmailConfirmation')
            ->with(
                $this->identicalTo('app_verify_email'),
                $this->callback(function (User $user) {
                    return $user->getEmail() === $this->dto->email;
                }),
                $this->callback(function (TemplatedEmail $email) {
                    $context = $email->getContext();
                    return $email->getTo()[0]->getAddress() === $this->dto->email
                        && isset($context['user'])
                        && $context['user'] instanceof User;
                })
            );

        // Act
        $this->service->register($this->dto);

        // Assert is implicit
    }

    #[Test]
    public function it_should_return_the_created_user_during_registration(): void
    {
        // Arrange
        $this->passwordHasher->method('hashPassword')->willReturn('hashed_secret_password');

        // Act
        $result = $this->service->register($this->dto);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($this->dto->email, $result->getEmail());
        $this->assertSame($this->dto->username, $result->getUsername());
        $this->assertSame('hashed_secret_password', $result->getPassword());
    }

    #[Test]
    public function it_should_delegate_email_confirmation_to_email_verifier(): void
    {
        // Arrange
        $request = new Request();

        $this->emailVerifier
            ->expects($this->once())
            ->method('handleEmailConfirmation')
            ->with($request, $this->user);

        // Act
        $this->service->handleEmailConfirmation($request, $this->user);

        // Assert is implicit
    }
}
