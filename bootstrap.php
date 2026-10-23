<?php

declare(strict_types=1);

$root = __DIR__;

require $root . '/vendor/autoload.php';

load_env($root . '/.env');

date_default_timezone_set(config('app.timezone'));

if (!config('app.debug')) {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

session_name('riverbird_session');
session_set_cookie_params([
    'lifetime' => config('app.session_lifetime'),
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
