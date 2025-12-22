<?php

namespace App\Twig\Components\Server;

use App\Entity\User;
use App\Repository\ServerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Server:Rail')]
class Rail extends AbstractController
{
    public function __construct(
        private readonly ServerRepository $serverRepository
    ) {}

    public function getServers(): array
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return [];
        }

        return $this->serverRepository->findForUser($user);
    }
}
