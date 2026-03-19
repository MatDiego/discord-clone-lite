<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\Mercure\Update;

final class MockMercurePublisher
{
    public function __invoke(Update $update): string
    {
        return $update->getId() ?? uniqid('mock_', true);
    }
}
