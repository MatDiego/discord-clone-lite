<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ServerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ServerController extends AbstractController
{

    public function rail(ServerRepository $serverRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return new Response('');
        }

        return $this->render('server/view.html.twig', [
            'servers' => $serverRepository->findForUser($user),
        ]);
    }
}
