<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\UpdateProfileRequest;
use App\Entity\User;
use App\Form\UpdateProfileType;
use App\Service\FriendInvitationService;
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
    public function index(
        ?User $displayedUser,
        FriendInvitationService $friendService,
    ): Response {
        if ($displayedUser === null) {
            /** @var User $displayedUser */
            $displayedUser = $this->getUser();
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $isSelf = $currentUser->getId()->equals($displayedUser->getId());
        $isFriend = false;
        $pendingInvitation = null;

        if (!$isSelf) {
            $isFriend = $friendService->areFriends($currentUser, $displayedUser);
            if (!$isFriend) {
                $pendingInvitation = $friendService->findPendingBetween($currentUser, $displayedUser);
            }
        }

        return $this->render('profile/index.html.twig', [
            'user'              => $displayedUser,
            'isSelf'            => $isSelf,
            'isFriend'          => $isFriend,
            'pendingInvitation' => $pendingInvitation,
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
