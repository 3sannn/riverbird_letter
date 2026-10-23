<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'RiverBird Letter'),
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(env('APP_URL', ''), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata'),
    'storage_path' => env('STORAGE_PATH', 'storage'),
    'template_path' => env('TEMPLATE_PATH', 'templates'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
    'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
    'login_lockout_minutes' => (int) env('LOGIN_LOCKOUT_MINUTES', 15),
    'upload_max_bytes' => (int) env('UPLOAD_MAX_BYTES', 10485760),
    'company' => [
        'name' => env('COMPANY_NAME', 'RiverBird Digital Solutions Pvt Ltd'),
        'tagline' => env('COMPANY_TAGLINE', 'Web Development & Digital Marketing Agency'),
        'email' => env('COMPANY_EMAIL', ''),
        'phone' => env('COMPANY_PHONE', ''),
        'address' => env('COMPANY_ADDRESS', ''),
        'gstin' => env('COMPANY_GSTIN', ''),
    ],
];
