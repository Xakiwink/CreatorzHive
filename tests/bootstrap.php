<?php

declare(strict_types=1);

define('CREATORZHIVE_PHPUNIT', true);

if (ob_get_level() === 0) {
    ob_start();
}

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';
require_once $root . '/backend/helpers/functions.php';

load_env(base_path('.env'));

// Use a separate DB only when DB_DATABASE_TEST is set in the environment (e.g. phpunit.xml or .env).
$dbTest = trim((string) (getenv('DB_DATABASE_TEST') !== false ? getenv('DB_DATABASE_TEST') : ($_ENV['DB_DATABASE_TEST'] ?? '')));
if ($dbTest !== '') {
    $_ENV['DB_DATABASE'] = $dbTest;
    putenv('DB_DATABASE=' . $dbTest);
}

require_once $root . '/backend/bootstrap-oop.php';
require_once $root . '/backend/bootstrap-procedural.php';
