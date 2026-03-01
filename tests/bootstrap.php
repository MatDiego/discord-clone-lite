<?php
declare(strict_types=1);

use DG\BypassFinals;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');
}

if (($_SERVER['APP_DEBUG'] ?? false) !== false) {
    umask(0000);
}

BypassFinals::enable();
