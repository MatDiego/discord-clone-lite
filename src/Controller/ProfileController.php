<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\UpdateProfileRequest;
use App\Entity\User;
use App\Form\UpdateProfileType;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProfileController extends AbstractController
{
    #[Route(path: '/profile/{id?}', name: 'app_profile', methods: ['GET'])]
    public function index(?User $displayedUser): Response
    {
        if ($displayedUser === null) {
            $displayedUser = $this->getUser();
        }

        if (!$displayedUser) {
            throw $this->createAccessDeniedException('Musisz być zalogowany, żeby wejść na profil.');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $displayedUser,
        ]);
    }

    #[Route(path: '/profile/edit', name: 'app_profile_edit', methods: ['GET', 'POST'], priority: 10)]
    public function edit(
        Request $request,
        ProfileService $profileService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $requestDto = UpdateProfileRequest::fromUser($user);
        $form = $this->createForm(UpdateProfileType::class, $requestDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profileService->updateProfile($user, $requestDto);

            $this->addFlash('success', 'Pomyślnie zaktualizowano Twój profil!');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
