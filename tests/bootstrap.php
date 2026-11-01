<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

// Load .env so DB credentials are available in integration tests.
// Real environment variables (e.g. CI) always win over .env values.
(static function (): void {
    $path = dirname(__DIR__) . '/.env';
    if (!is_file($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);
        if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = stripslashes(substr($value, 1, -1));
        } elseif (str_starts_with($value, "'") && str_ends_with($value, "'")) {
            $value = substr($value, 1, -1);
        }
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
        }
    }
})();
