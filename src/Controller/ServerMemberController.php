<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Entity\ServerMember;
use App\Entity\User;
use App\Form\MemberRoleType;
use App\Security\Voter\ServerMemberVoter;
use App\Security\Voter\ServerVoter;
use App\Service\ServerMemberService;
use App\Service\ServerRoleService;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/members')]
final class ServerMemberController extends AbstractController
{
    public function __construct(
        private readonly ServerMemberService $serverMemberService,
    ) {
    }

    #[Route('/{memberId}/kick', name: 'app_member_kick', methods: ['POST'])]
    #[IsGranted(ServerMemberVoter::KICK, subject: 'member')]
    public function kick(
        Request $request,
        #[MapEntity(id: 'memberId')] ServerMember $member,
    ): Response {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('member_kick', $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        $this->serverMemberService->kick($member);
        $this->addFlash('success', sprintf('Użytkownik %s został wyrzucony z serwera.', $member->getUser()->getUsername()));

        return $this->render('server/stream_moderation.stream.html.twig', [
            'modal' => 'kickMemberModal',
        ], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
    }

    #[Route('/{memberId}/ban', name: 'app_member_ban', methods: ['POST'])]
    #[IsGranted(ServerMemberVoter::BAN, subject: 'member')]
    public function ban(
        Request $request,
        #[MapEntity(id: 'memberId')] ServerMember $member,
    ): Response {
        $token = $request->request->getString('_csrf_token');

        if (!$this->isCsrfTokenValid('member_ban', $token)) {
            throw new BadRequestHttpException('Nieprawidłowy token CSRF.');
        }

        $duration = $request->request->getString('duration') ?: null;

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $this->serverMemberService->ban($member, $duration, $currentUser);
        $this->addFlash('success', sprintf('Użytkownik %s został zbanowany.', $member->getUser()->getUsername()));

        return $this->render('server/stream_moderation.stream.html.twig', [
            'modal' => 'banMemberModal',
        ], new Response('', 200, ['Content-Type' => 'text/vnd.turbo-stream.html']));
    }

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
