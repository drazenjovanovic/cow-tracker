<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Core\Config;

final class RateLimitConfig
{
    public function __construct(
        public readonly int $maxAttempts,
        public readonly int $decaySeconds,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        return new self(
            maxAttempts:  (int) $config->get('rate_limit.max_attempts', 5),
            decaySeconds: (int) $config->get('rate_limit.decay_seconds', 900),
        );
    }
}
