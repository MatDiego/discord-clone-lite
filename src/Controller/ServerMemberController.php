<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\ServerMember;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[Route('/servers/{serverId}/members')]
class ServerMemberController extends AbstractController
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
}
