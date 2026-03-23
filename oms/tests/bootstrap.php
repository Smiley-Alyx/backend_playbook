<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

$hasAppEnv = isset($_SERVER['APP_ENV']) || isset($_ENV['APP_ENV']) || getenv('APP_ENV') !== false;

if (!$hasAppEnv && method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
