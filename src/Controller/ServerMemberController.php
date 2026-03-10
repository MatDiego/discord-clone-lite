<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Entity\ServerMember;
use App\Form\MemberRoleType;
use App\Security\Voter\ServerVoter;
use App\Service\ServerRoleService;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/members')]
final class ServerMemberController extends AbstractController
{

    #[Route('/{memberId}/roles', name: 'app_member_roles_fragment')]
    public function memberRolesFragment(
        #[MapEntity(id: 'memberId')] ServerMember $member,
        #[MapEntity(id: 'serverId')] Server $server
    ): Response
    {
        return $this->render('components/Server/_role_content.html.twig', [
            'member' => $member,
            'server' => $server,
        ]);
    }

    #[Route('/{memberId}/roles/edit', name: 'app_member_roles_edit', methods: ['GET', 'POST'])]
    #[IsGranted(ServerVoter::MANAGE_ROLES, subject: 'server')]
    public function editMemberRoles(
        Request $request,
        #[MapEntity(id: 'memberId')] ServerMember $member,
        #[MapEntity(id: 'serverId')] Server $server,
        ServerRoleService $serverRoleService
    ): Response
    {
        $currentRoles = array_map(fn($mr) => $mr->getRole(), $member->getMemberRoles()->toArray());

        $userRoles = $serverRoleService->getRolesForServer($server);

        $form = $this->createForm(MemberRoleType::class, ['roles' => $currentRoles], [
            'user_roles' => $userRoles
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedRoles = $form->get('roles')->getData();
            $rolesArray = $selectedRoles instanceof Collection
                ? $selectedRoles->toArray()
                : (array) $selectedRoles;

            $serverRoleService->updateMemberRoles($member, $rolesArray);

            return $this->redirectToRoute('app_member_roles_fragment', [
                'serverId' => $server->getId(),
                'memberId' => $member->getId()
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('components/Server/_role_edit.html.twig', [
            'member' => $member,
            'server' => $server,
            'form' => $form->createView()
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK)
        );
    }
}
