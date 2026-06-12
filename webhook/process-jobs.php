<?php
/**
 * Root-level webhook/process-jobs.php that includes the actual webhook from /public/webhook/process-jobs.php
 *
 * This exists so that https://domain.com/webhook/process-jobs.php works
 * even though InfinityFree points the domain to the project root,
 * not to the /public subdirectory.
 */

require_once __DIR__ . '/../public/webhook/process-jobs.php';
