<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Core\Config;

final class MailConfig
{
    public function __construct(
        public readonly string $driver,
        public readonly string $from,
        public readonly string $fromName,
        public readonly string $logPath,
        public readonly string $host,
        public readonly int    $port,
        public readonly string $username,
        public readonly string $password,
        public readonly bool   $async,
    ) {
    }

    public static function fromConfig(Config $config): self
    {
        return new self(
            driver:   $config->string('mail.driver', 'log'),
            from:     $config->string('mail.from', 'noreply@example.com'),
            fromName: $config->string('mail.from_name', 'Cow Tracker'),
            logPath:  $config->string('mail.log'),
            host:     $config->string('mail.host'),
            port:     (int) $config->get('mail.port', 587),
            username: $config->string('mail.username'),
            password: $config->string('mail.password'),
            async:    filter_var($config->get('mail.async', false), FILTER_VALIDATE_BOOLEAN),
        );
    }
}
