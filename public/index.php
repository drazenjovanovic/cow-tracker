<?php

declare(strict_types=1);

// PHP built-in server: let it serve existing static files (CSS/JS) itself.
if (PHP_SAPI === 'cli-server') {
    $requested = __DIR__ . (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if (is_file($requested) && $requested !== __FILE__) {
        return false;
    }
}

require dirname(__DIR__) . '/src/bootstrap.php';

App\AppFactory::create(dirname(__DIR__))->run();
