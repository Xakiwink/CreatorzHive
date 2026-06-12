<?php
/**
 * Root-level setup.php that includes the actual setup from /public/setup.php
 *
 * This exists so that https://domain.com/setup.php works
 * even though InfinityFree points the domain to the project root,
 * not to the /public subdirectory.
 */

require_once __DIR__ . '/public/setup.php';
