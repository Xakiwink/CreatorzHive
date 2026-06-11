#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__);

/** @return list<string> */
function oop_global_function_names(string $root): array
{
    $names = [];
    $scan = array_merge(
        glob($root . '/backend/compat/*.php') ?: [],
        glob($root . '/backend/helpers/*.php') ?: [],
        glob($root . '/backend/core/*.php') ?: [],
        glob($root . '/backend/middleware/*.php') ?: [],
        glob($root . '/backend/services/*.php') ?: [],
        glob($root . '/backend/http.php') ?: []
    );
    foreach ($scan as $file) {
        $code = file_get_contents($file);
        if (preg_match_all('/function\s+([a-z][a-z0-9_]*)\s*\(/', $code, $m)) {
            foreach ($m[1] as $fn) {
                $names[$fn] = true;
            }
        }
    }

    return array_keys($names);
}

function oop_strip_existing_use_functions(string $code): string
{
    return preg_replace('/^use function [^;]+;\n/m', '', $code) ?? $code;
}

function oop_apply_use_functions(string $code, array $needed): string
{
    if ($needed === []) {
        return $code;
    }
    sort($needed);
    $uses = '';
    foreach ($needed as $fn) {
        $uses .= "use function {$fn};\n";
    }
    if (preg_match('/^namespace[^;]+;\s*/m', $code, $m, PREG_OFFSET_CAPTURE)) {
        $insertAt = $m[0][1] + strlen($m[0][0]);

        return substr($code, 0, $insertAt) . "\n" . $uses . substr($code, $insertAt);
    }

    return $code;
}

$globals = oop_global_function_names($root);
$targets = array_merge(
    glob($root . '/src/Controllers/*.php') ?: [],
    glob($root . '/src/Services/*.php') ?: []
);

foreach ($targets as $path) {
    $code = file_get_contents($path);
    $needed = [];
    foreach ($globals as $fn) {
        if (preg_match('/(?<![a-zA-Z0-9_])' . preg_quote($fn, '/') . '\s*\(/', $code) === 1) {
            $needed[] = $fn;
        }
    }
    $code = oop_strip_existing_use_functions($code);
    $code = oop_apply_use_functions($code, $needed);
    file_put_contents($path, $code);
    if ($needed !== []) {
        echo basename($path) . ' (' . count($needed) . " imports)\n";
    }
}

echo "Done.\n";
