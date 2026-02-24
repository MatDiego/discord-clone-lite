<?php

namespace App\Controller;

use App\Dto\AddOverrideRequest;
use App\Entity\Channel;
use App\Entity\Server;
use App\Form\AddOverrideType;
use App\Form\ChannelPermissionsType;
use App\Security\Voter\ChannelVoter;
use App\Service\ChannelPermissionService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/channels/{channelId}/permissions')]
class ChannelPermissionsController extends AbstractController
{
    public function __construct(
        private readonly ChannelPermissionService $permissionService,
    ) {
    }

    #[Route('/', name: 'app_channel_permissions', requirements: ['serverId' => Requirement::UUID_V7, 'channelId' => Requirement::UUID_V7])]
    #[IsGranted(ChannelVoter::MANAGE_PERMISSIONS, subject: 'channel')]
    public function permissions(
        Request $request,
        #[MapEntity(id: 'channelId')] Channel $channel,
        #[MapEntity(id: 'serverId')] Server $server,
    ): Response {
        return $this->render('channel/permissions.html.twig', [
            'channel' => $channel,
            'server' => $server,
            'selected' => $this->permissionService->normalizeSelected($request->query->get('selected')),
        ]);
    }

    #[Route('/add', name: 'app_channel_permission_add', requirements: ['serverId' => Requirement::UUID_V7, 'channelId' => Requirement::UUID_V7], methods: ['POST'])]
    #[IsGranted(ChannelVoter::MANAGE_PERMISSIONS, subject: 'channel')]
    public function addOverride(
        Request $request,
        #[MapEntity(id: 'channelId')] Channel $channel,
        #[MapEntity(id: 'serverId')] Server $server,
    ): Response {
        $form = $this->createForm(AddOverrideType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->redirectToRoute('app_channel_permissions', [
                'serverId' => $server->getId(),
                'channelId' => $channel->getId(),
            ]);
        }

        /** @var AddOverrideRequest $data */
        $data = $form->getData();

        $targetType = $data->targetType;
        $targetId = $data->targetId;

        if (!$this->permissionService->targetExists($targetType, $targetId)) {
            $this->addFlash('error', 'Nie znaleziono celu.');
            return $this->redirectToRoute('app_channel_permissions', [
                'serverId' => $server->getId(),
                'channelId' => $channel->getId(),
            ]);
        }

        return $this->redirectToRoute('app_channel_permissions', [
            'serverId' => $server->getId(),
            'channelId' => $channel->getId(),
            'selected' => $targetType . ':' . $this->permissionService->normalizeUuid($targetId),
        ]);
    }

    #[Route('/save', name: 'app_channel_permission_save', requirements: ['serverId' => Requirement::UUID_V7, 'channelId' => Requirement::UUID_V7], methods: ['POST'])]
    #[IsGranted(ChannelVoter::MANAGE_PERMISSIONS, subject: 'channel')]
    public function saveOverrides(
        Request $request,
        #[MapEntity(id: 'channelId')] Channel $channel,
        #[MapEntity(id: 'serverId')] Server $server,
    ): Response {
        $targetType = $request->request->get('target_type');
        $targetId = $this->permissionService->normalizeUuid($request->request->get('target_id'));

        $form = $this->createForm(ChannelPermissionsType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'Nieprawidłowy formularz lub token bezpieczeństwa.');
            return $this->redirectToRoute('app_channel_permissions', [
                'serverId' => $server->getId(),
                'channelId' => $channel->getId(),
                'selected' => $targetType . ':' . $targetId,
            ]);
        }

        try {
            $this->permissionService->saveTargetOverrides($channel, $targetType, $targetId, $form->getData());
            $this->addFlash('success', 'Uprawnienia zapisane.');
        } catch (\InvalidArgumentException) {
            $this->addFlash('error', 'Nie powiodło się zapisywanie uprawnień.');
            return $this->redirectToRoute('app_channel_permissions', [
                'serverId' => $server->getId(),
                'channelId' => $channel->getId(),
                'selected' => $targetType . ':' . $targetId,
            ]);
        }

        $key = $targetType . ':' . $targetId;
        if (!$this->permissionService->getOverrideGroups($channel)->get($key)) {
            return $this->redirectToRoute('app_channel_permissions', [
                'serverId' => $server->getId(),
                'channelId' => $channel->getId(),
            ]);
        }

        return $this->redirectToRoute('app_channel_permissions', [
            'serverId' => $server->getId(),
            'channelId' => $channel->getId(),
            'selected' => $targetType . ':' . $targetId,
        ]);
    }

    #[Route('/remove', name: 'app_channel_permission_remove', requirements: ['serverId' => Requirement::UUID_V7, 'channelId' => Requirement::UUID_V7], methods: ['POST'])]
    #[IsGranted(ChannelVoter::MANAGE_PERMISSIONS, subject: 'channel')]
    public function removeOverrides(
        Request $request,
        #[MapEntity(id: 'channelId')] Channel $channel,
        #[MapEntity(id: 'serverId')] Server $server,
    ): Response {
        if (!$this->isCsrfTokenValid('channel_permissions_remove_' . $channel->getId(), $request->request->get('_csrf_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('app_channel_permissions', [
                'serverId' => $server->getId(),
                'channelId' => $channel->getId(),
            ]);
        }

        $targetType = $request->request->get('target_type');
        $targetId = $this->permissionService->normalizeUuid($request->request->get('target_id'));

        $this->permissionService->clearTargetOverrides($channel, $targetType, $targetId);
        $this->addFlash('success', 'Nadpisania usunięte.');

        return $this->redirectToRoute('app_channel_permissions', [
            'serverId' => $server->getId(),
            'channelId' => $channel->getId(),
        ]);
    }
}
