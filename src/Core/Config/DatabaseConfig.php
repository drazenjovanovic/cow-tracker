<?php

declare(strict_types=1);

namespace App\Core\Config;

use App\Core\Config;

final class DatabaseConfig
{
    public readonly string $dsn;

    public function __construct(
        public readonly string $host,
        public readonly int    $port,
        public readonly string $name,
        public readonly string $user,
        public readonly string $password,
    ) {
        $this->dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $name);
    }

    public static function fromConfig(Config $config): self
    {
        return new self(
            host:     $config->string('db.host', '127.0.0.1'),
            port:     (int) $config->get('db.port', 3306),
            name:     $config->string('db.name', 'cow_tracker'),
            user:     $config->string('db.user', 'root'),
            password: $config->string('db.password'),
        );
    }
}
