<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Core\Config;

final class AppConfig
{
    public function __construct(
        public readonly string $name,
        public readonly string $url,
        public readonly string $timezone,
        public readonly bool   $debug,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        return new self(
            name:     $config->string('app.name', 'App'),
            url:      $config->string('app.url', 'http://localhost:8000'),
            timezone: $config->string('app.timezone', 'UTC'),
            debug:    filter_var($config->get('app.debug', false), FILTER_VALIDATE_BOOLEAN),
        );
    }
}
