<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$router = require dirname(__DIR__) . '/config/routes.php';

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$base = rtrim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
if ($base && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base)) ?: '/';
}

$router->dispatch(request_method(), $uri);
