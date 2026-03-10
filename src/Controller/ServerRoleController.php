<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\CustomRoleRequest;
use App\Entity\Server;
use App\Entity\UserRole;
use App\Form\CustomRoleType;
use App\Security\Voter\ServerVoter;
use App\Service\ServerRoleService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/roles', requirements: ['serverId' => Requirement::UUID_V7])]
final class ServerRoleController extends AbstractController
{
    public function __construct(
        private readonly ServerRoleService $serverRoleService
    ) {
    }

    #[Route('/new', name: 'app_server_role_create', methods: ['GET', 'POST'])]
    #[IsGranted(ServerVoter::MANAGE_ROLES, subject: 'server')]
    public function create(
        Request $request,
        #[MapEntity(id: 'serverId')] Server $server
    ): Response {
        $form = $this->createForm(CustomRoleType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CustomRoleRequest $roleData */
            $roleData = $form->getData();

            $isDuplicate = false;
            foreach ($server->getUserRoles() as $existingRole) {
                if (mb_strtolower($existingRole->getName()) === mb_strtolower($roleData->name)) {
                    $isDuplicate = true;
                    break;
                }
            }

            if ($isDuplicate) {
                $form->get('name')->addError(new FormError('Rola o takiej nazwie już istnieje na tym serwerze.'));
            } else {
                $this->serverRoleService->createCustomRole($server, $roleData);

                $this->addFlash('success', 'Utworzono nową rolę pomyślnie!');

                return $this->redirectToRoute('app_server_edit', ['serverId' => $server->getId()]);
            }
        }

        return $this->render('server/role_form.html.twig', [
            'server' => $server,
            'form' => $form->createView(),
            'is_edit' => false,
        ]);
    }

    #[Route('/{roleId}/edit', name: 'app_server_role_edit', requirements: ['roleId' => Requirement::UUID_V7], methods: ['GET', 'POST'])]
    #[IsGranted(ServerVoter::MANAGE_ROLES, subject: 'server')]
    public function edit(
        Request $request,
        #[MapEntity(id: 'serverId')] Server $server,
        #[MapEntity(id: 'roleId')] UserRole $role
    ): Response {
        if ($role->getServer() !== $server) {
            throw $this->createAccessDeniedException('Rola nie należy do tego serwera.');
        }

        $roleData = new CustomRoleRequest();
        $roleData->name = $role->getName();
        $roleData->permissions = array_map(fn($rp) => $rp->getPermission(), $role->getRolePermissions()->toArray());

        $form = $this->createForm(CustomRoleType::class, $roleData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var CustomRoleRequest $submittedData */
            $submittedData = $form->getData();

            $isDuplicate = false;
            foreach ($server->getUserRoles() as $existingRole) {
                if ($existingRole !== $role && mb_strtolower($existingRole->getName()) === mb_strtolower($submittedData->name)) {
                    $isDuplicate = true;
                    break;
                }
            }

            if ($isDuplicate) {
                $form->get('name')->addError(new FormError('Rola o takiej nazwie już istnieje na tym serwerze.'));
            } else {
                $this->serverRoleService->updateCustomRole($role, $submittedData);

                $this->addFlash('success', 'Zaktualizowano rolę pomyślnie!');

                return $this->redirectToRoute('app_server_edit', ['serverId' => $server->getId()]);
            }
        }

        return $this->render('server/role_form.html.twig', [
            'server' => $server,
            'form' => $form->createView(),
            'is_edit' => true,
        ]);
    }

    #[Route('/{roleId}/delete', name: 'app_server_role_delete', requirements: ['roleId' => Requirement::UUID_V7], methods: ['POST'])]
    #[IsGranted(ServerVoter::MANAGE_ROLES, subject: 'server')]
    public function delete(
        Request $request,
        #[MapEntity(id: 'serverId')] Server $server,
        #[MapEntity(id: 'roleId')] UserRole $role
    ): Response {
        if ($role->getServer() !== $server) {
            throw $this->createAccessDeniedException('Rola nie należy do tego serwera.');
        }

        if ($this->isCsrfTokenValid('delete_role_' . $role->getId()->toRfc4122(), $request->request->getString('_csrf_token'))) {
            $this->serverRoleService->deleteRole($role);
            $this->addFlash('success', 'Rola została pomyślnie usunięta.');
        } else {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
        }

        return $this->redirectToRoute('app_server_edit', ['serverId' => $server->getId()]);
    }
}
