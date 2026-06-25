<?php

/**
 * Laravel + Livewire router script for PHP's built-in server.
 *
 * PHP's built-in server returns 404 for URLs that look like static files
 * (e.g. /livewire/livewire.min.js) when the file does not exist on disk,
 * instead of forwarding the request to the application. This router
 * script fixes that by falling through to public/index.php for any
 * request that doesn't match an actual file in the public directory.
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'
);

// If the requested file exists in the public directory, let the
// built-in server handle it directly (images, compiled assets, etc.).
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

// Otherwise, forward to Laravel's front controller.
require_once __DIR__ . '/public/index.php';
