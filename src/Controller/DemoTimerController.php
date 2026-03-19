<?php

declare(strict_types=1);

namespace App\Controller;

use Redis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class DemoTimerController extends AbstractController
{
    private const string NEXT_RESET_KEY = 'demo:next_reset';

    #[Route(path: '/api/demo/timer', name: 'api_demo_timer', methods: ['GET'])]
    public function timer(Redis $redis, int $sessionLifetime): JsonResponse
    {
        $nextReset = $redis->get(self::NEXT_RESET_KEY);

        if (!\is_string($nextReset)) {
            $secondsLeft = $sessionLifetime;
        } else {
            $secondsLeft = max(0, (int) $nextReset - time());
        }

        return $this->json([
            'secondsLeft' => $secondsLeft,
            'totalSeconds' => $sessionLifetime,
        ], headers: ['Cache-Control' => 'no-store']);
    }
}
