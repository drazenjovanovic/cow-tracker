<?php

declare(strict_types=1);

// Load .env before anything else so env vars are available to config/config.php.
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
        // Real environment variables always win over the .env file.
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
})();

// Minimal PSR-4 style autoloader: App\Foo\Bar -> src/Foo/Bar.php (no Composer needed).
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
