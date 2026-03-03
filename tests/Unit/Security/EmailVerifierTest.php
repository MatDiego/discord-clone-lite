<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;


interface TestVerifyEmailHelperForVerifierInterface extends VerifyEmailHelperInterface
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     * This interface is a workaround for PHPUnit mocking.
     * VerifyEmailHelperInterface defines validateEmailConfirmationFromRequest as a @method `in` PHPDoc,
     * which PHPUnit cannot natively mock without throwing an exception.
     */
    public function validateEmailConfirmationFromRequest(Request $request, string $userId, string $userEmail): void;
}

final class EmailVerifierTest extends TestCase
{
    private TestVerifyEmailHelperForVerifierInterface $verifyEmailHelper;
    private MailerInterface $mailer;
    private UserRepository $userRepository;
    private EmailVerifier $emailVerifier;

    #[Override]
    protected function setUp(): void
    {

        $this->verifyEmailHelper = $this->createMock(TestVerifyEmailHelperForVerifierInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->emailVerifier = new EmailVerifier(
            $this->verifyEmailHelper,
            $this->mailer,
            $this->userRepository
        );
    }

    #[Test]
    public function it_should_generate_signature_for_email_confirmation(): void
    {
        // Arrange
        $user = new User('test@example.com', 'TestUser', 'password');
        $email = new TemplatedEmail();

        $signatureComponents = clone new VerifyEmailSignatureComponents(new \DateTimeImmutable('+1 hour'), 'http://localhost/verify', time());

        $this->verifyEmailHelper
            ->expects($this->once())
            ->method('generateSignature')
            ->with(
                'app_verify_email',
                (string) $user->getId(),
                $user->getEmail(),
                ['id' => (string) $user->getId()]
            )
            ->willReturn($signatureComponents);

        // Act
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user, $email);

        // Assert is implicit
    }

    #[Test]
    public function it_should_send_the_templated_email_with_signature_context(): void
    {
        // Arrange
        $user = new User('test@example.com', 'TestUser', 'password');
        $email = new TemplatedEmail();

        $signatureComponents = clone new VerifyEmailSignatureComponents(new \DateTimeImmutable('+1 hour'), 'http://localhost/verify', time());

        $this->verifyEmailHelper->method('generateSignature')->willReturn($signatureComponents);

        $this->mailer
            ->expects($this->once())
            ->method('send');

        // Act
        $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user, $email);

        // Assert
        $context = $email->getContext();
        $this->assertSame('http://localhost/verify', $context['signedUrl']);
        $this->assertSame('%count% hour|%count% hours', $context['expiresAtMessageKey']);
        $this->assertSame(['%count%' => 1], $context['expiresAtMessageData']);
    }

    #[Test]
    public function it_should_validate_email_confirmation_request_using_helper(): void
    {
        // Arrange
        $request = new Request();
        $user = new User('test@example.com', 'TestUser', 'password');

        $this->verifyEmailHelper
            ->expects($this->once())
            ->method('validateEmailConfirmationFromRequest')
            ->with($request, (string) $user->getId(), $user->getEmail());

        // Act
        $this->emailVerifier->handleEmailConfirmation($request, $user);

        // Assert is implicit
    }

    #[Test]
    public function it_should_mark_user_as_verified_and_persist_to_database(): void
    {
        // Arrange
        $request = new Request();
        $user = new User('test@example.com', 'TestUser', 'password');

        $this->assertFalse($user->isVerified());

        $this->userRepository
            ->expects($this->once())
            ->method('add')
            ->with($user);

        $this->userRepository
            ->expects($this->once())
            ->method('flush');

        // Act
        $this->emailVerifier->handleEmailConfirmation($request, $user);

        // Assert
        $this->assertTrue($user->isVerified());
    }
}
