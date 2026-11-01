<?php

declare(strict_types=1);

$base = dirname(__DIR__);
$storage = rtrim((string) (getenv('STORAGE_PATH') ?: $base . '/storage'), '/');

return [
    'app' => [
        'name'     => 'Cow Tracker',
        'url'      => getenv('APP_URL')      ?: 'http://localhost:8000',
        'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',
        'debug'    => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN),
    ],
    'log' => [
        'level' => strtolower((string) (getenv('LOG_LEVEL') ?: 'info')),
    ],
    'mail' => [
        'driver'    => getenv('MAIL_DRIVER')    ?: 'log',
        'async'     => filter_var(getenv('MAIL_ASYNC') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'from'      => getenv('MAIL_FROM')      ?: 'noreply@example.com',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'Cow Tracker',
        'log'       => $storage . '/mail.log',
        'host'      => getenv('MAIL_HOST')      ?: '',
        'port'      => (int) (getenv('MAIL_PORT') ?: 587),
        'username'  => getenv('MAIL_USERNAME')  ?: '',
        'password'  => getenv('MAIL_PASSWORD')  ?: '',
    ],
    'db' => [
        'host'     => getenv('DB_HOST')     ?: '127.0.0.1',
        'port'     => getenv('DB_PORT')     ?: '3306',
        'name'     => getenv('DB_NAME')     ?: 'cow_tracker',
        'user'     => getenv('DB_USER')     ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],
    'session' => [
        'name' => 'cow_session',
        'path' => $storage . '/sessions',
    ],
    'download' => [
        // Any file can be served here; the setup script creates a harmless placeholder.
        'file' => $storage . '/downloads/installer.exe',
        'name' => 'installer.exe',
    ],
    'rate_limit' => [
        'max_attempts'  => 5,    // failed login attempts before lockout
        'decay_seconds' => 60,  // lockout window (1 minute)
    ],
    'messages' => [
        'thank_you' => 'thank you',
    ],
];
