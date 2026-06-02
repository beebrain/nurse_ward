<?php

/**
 * Serves whitelisted CSS/JS when the reverse proxy rewrites non-.php URIs to index.php.
 * Direct .php requests are handled by php-fpm without that rewrite.
 */

declare(strict_types=1);

$type = (string) ($_GET['t'] ?? '');
$name = basename((string) ($_GET['f'] ?? ''));

$allowed = [
    'css' => [
        'app.css'            => ['path' => __DIR__ . '/css/app.css', 'mime' => 'text/css'],
        'census-layout.css'  => ['path' => __DIR__ . '/css/census-layout.css', 'mime' => 'text/css'],
    ],
    'js' => [
        'census_entry.js' => ['path' => __DIR__ . '/js/census_entry.js', 'mime' => 'application/javascript'],
    ],
];

if (! isset($allowed[$type][$name])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$asset = $allowed[$type][$name];
if (! is_file($asset['path']) || ! is_readable($asset['path'])) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Missing file';
    exit;
}

header('Content-Type: ' . $asset['mime'] . '; charset=utf-8');
header('Cache-Control: public, max-age=86400');
readfile($asset['path']);
