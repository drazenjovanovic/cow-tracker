<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Config\DatabaseConfig;
use PDO;
use PDOStatement;

final class Database
{
    private const SLOW_QUERY_MS = 100;

    private ?PDO $pdo = null;

    public function __construct(
        private readonly DatabaseConfig   $config,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function ping(): bool
    {
        try {
            $this->pdo()->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Prepare and execute a statement, logging a warning if it exceeds SLOW_QUERY_MS.
     * PHP ints are bound as PARAM_INT, nulls as PARAM_NULL, everything else as PARAM_STR.
     *
     * @param array<int|string, mixed> $params
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        $start = hrtime(true);
        $stmt  = $this->pdo()->prepare($sql);
        foreach ($params as $key => $value) {
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue(is_int($key) ? $key + 1 : ':' . $key, $value, $type);
        }
        $stmt->execute();
        $ms = (int) ((hrtime(true) - $start) / 1_000_000);

        if ($ms >= self::SLOW_QUERY_MS) {
            $this->logger?->warning('Slow query detected', ['ms' => $ms, 'sql' => $sql]);
        }

        return $stmt;
    }

    public function lastInsertId(): int
    {
        return (int) $this->pdo()->lastInsertId();
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = new PDO($this->config->dsn, $this->config->user, $this->config->password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return $this->pdo;
    }
}
