<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists(__DIR__ . '/../public_html' . $uri)) {
    return false;
}

// Fast 404 for missing static assets to prevent queueing heavy Laravel boots on PHP CLI server
if (preg_match('/\.(?:css|js|map|png|jpe?g|gif|svg|ico|webp|woff2?|ttf|eot)$/i', $uri)) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo '404 Not Found';
    exit;
}

require_once __DIR__ . '/../public_html/index.php';
