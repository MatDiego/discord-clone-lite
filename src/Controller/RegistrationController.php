<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegistrationRequest;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, RateLimiterFactoryInterface $registrationLimiter): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_dashboard');
        }

        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $ip = $request->getClientIp() ?? 'unknown';
            $limiter = $registrationLimiter->create($ip);
            if (!$limiter->consume()->isAccepted()) {
                $this->addFlash('error', 'Zbyt wiele prób rejestracji. Spróbuj ponownie za kilka minut.');

                return $this->redirectToRoute('app_register');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RegistrationRequest $registrationRequest */
            $registrationRequest = $form->getData();
            $user = $this->registrationService->register($registrationRequest);

            $this->addFlash('success', 'Rejestracja udana! Sprawdź email, aby aktywować konto.');
            $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $user->getEmail());

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request,
        TranslatorInterface $translator,
        UserRepository $userRepository
    ): Response {
        $id = $request->query->get('id');

        if (null === $id) {
            $this->addFlash('verify_email_error', 'Link jest nieprawidłowy lub konto zostało usunięte. Zarejestruj się ponownie.');
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            $this->addFlash('verify_email_error', 'Link jest nieprawidłowy lub konto zostało usunięte. Zarejestruj się ponownie.');
            return $this->redirectToRoute('app_register');
        }

        try {
            $this->registrationService->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        return $this->render('registration/verified.html.twig');
    }
}
