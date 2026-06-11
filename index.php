<?php

declare(strict_types=1);

/**
 * Fallback front controller when the vhost document root is this project folder
 * (not public/). Apache uses DirectoryIndex index.php before directory listings.
 */
require __DIR__ . '/public/index.php';
