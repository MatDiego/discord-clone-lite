<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\RegistrationRequest;
use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegistrationService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EmailVerifier $emailVerifier,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerifier $emailVerifier,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function register(RegistrationRequest $dto): User
    {
        $user = new User($dto->email, $dto->username, '');
        $user->setPassword($this->passwordHasher->hashPassword($user, $dto->plainPassword));

        $this->em->persist($user);
        $this->em->flush();

        $this->sendVerificationEmail($user);

        return $user;
    }

    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->emailVerifier->handleEmailConfirmation($request, $user);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendVerificationEmail(User $user): void
    {
        $this->emailVerifier->sendEmailConfirmation(
            'app_verify_email',
            $user,
            (new TemplatedEmail())
                ->from(new Address('admin@example.com', 'ChatApp Bot'))
                ->to($user->getEmail())
                ->subject('Potwierdź swój adres email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'user' => $user,
                ])
        );
    }
}
