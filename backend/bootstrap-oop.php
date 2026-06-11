<?php

declare(strict_types=1);

/**
 * Object-oriented application layer (PSR-4). Loads before procedural bootstrap.
 */
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    return;
}

require_once $autoload;

if (class_exists(\CreatorzHive\Core\Application::class)) {
    \CreatorzHive\Core\Application::boot();
}
