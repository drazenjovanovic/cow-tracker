<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Core\Config;

final class LogConfig
{
    public function __construct(
        public readonly string $level,
        public readonly string $dir,
    ) {
    }

    public static function fromConfig(Config $config, string $basePath): self
    {
        return new self(
            level: $config->string('log.level', 'info'),
            dir:   $basePath . '/storage/logs',
        );
    }
}
