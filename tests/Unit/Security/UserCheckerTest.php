<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User as AppUser;
use App\Security\UserChecker;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserCheckerTest extends TestCase
{
    private UserChecker $userChecker;

    #[Override]
    protected function setUp(): void
    {
        $this->userChecker = new UserChecker();
    }

    #[Test]
    public function it_should_ignore_non_app_user_during_pre_auth(): void
    {
        // Arrange
        $user = $this->createStub(UserInterface::class);

        // Assert
        $this->expectNotToPerformAssertions();

        // Act
        $this->userChecker->checkPreAuth($user);
    }

    #[Test]
    public function it_should_ignore_app_user_during_pre_auth(): void
    {
        // Arrange
        $user = new AppUser('test@example.com', 'TestUser', 'password');

        // Assert
        $this->expectNotToPerformAssertions();

        // Act
        $this->userChecker->checkPreAuth($user);
    }

    #[Test]
    public function it_should_ignore_non_app_user_during_post_auth(): void
    {
        // Arrange
        $user = $this->createStub(UserInterface::class);

        // Assert
        $this->expectNotToPerformAssertions();

        // Act
        $this->userChecker->checkPostAuth($user);
    }

    #[Test]
    public function it_should_throw_exception_during_post_auth_if_app_user_is_not_verified(): void
    {
        // Arrange
        $user = new AppUser('test@example.com', 'TestUser', 'password');

        // Assert
        $this->expectException(CustomUserMessageAccountStatusException::class);
        $this->expectExceptionMessage('Twoje konto nie jest aktywne. Sprawdź email i kliknij w link aktywacyjny.');

        // Act
        $this->userChecker->checkPostAuth($user);
    }

    #[Test]
    public function it_should_allow_app_user_during_post_auth_when_verified(): void
    {
        // Arrange
        $user = new AppUser('test@example.com', 'TestUser', 'password');
        $user->setIsVerified(true);

        // Assert
        $this->expectNotToPerformAssertions();

        // Act
        $this->userChecker->checkPostAuth($user);
    }
}
