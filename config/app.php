<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'AT Loops OS',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Africa/Cairo',
    'default_locale' => $_ENV['APP_DEFAULT_LOCALE'] ?? 'ar',
    'fallback_locale' => $_ENV['APP_FALLBACK_LOCALE'] ?? 'en',
    'locales' => [
        'ar' => [
            'name' => 'العربية',
            'dir' => 'rtl',
            'font' => 'Cairo',
        ],
        'en' => [
            'name' => 'English',
            'dir' => 'ltr',
            'font' => 'Inter',
        ],
    ],
    'session' => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 7200),
        'secure' => filter_var($_ENV['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'cookie_name' => $_ENV['SESSION_COOKIE_NAME'] ?? 'atloops_session',
    ],
];
