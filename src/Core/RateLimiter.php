<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Config\RateLimitConfig;
use DateTimeImmutable;

final class RateLimiter
{
    public function __construct(
        private readonly Database        $db,
        private readonly RateLimitConfig $config,
    ) {
    }

    public function tooManyAttempts(string $key, ?int $maxAttempts = null): bool
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT attempts FROM rate_limits WHERE key_hash = ? AND expires_at > NOW()'
        );
        $stmt->execute([$this->hash($key)]);
        $attempts = $stmt->fetchColumn();

        return $attempts !== false && (int) $attempts >= ($maxAttempts ?? $this->config->maxAttempts);
    }

    public function hit(string $key, ?int $decaySeconds = null): void
    {
        $decay     = $decaySeconds ?? $this->config->decaySeconds;
        $expiresAt = (new DateTimeImmutable())
            ->modify("+{$decay} seconds")
            ->format('Y-m-d H:i:s');

        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO rate_limits (key_hash, attempts, expires_at)
             VALUES (?, 1, ?)
             ON DUPLICATE KEY UPDATE
                 attempts   = IF(expires_at <= NOW(), 1, attempts + 1),
                 expires_at = IF(expires_at <= NOW(), ?, expires_at)'
        );
        $stmt->execute([$this->hash($key), $expiresAt, $expiresAt]);
    }

    public function clear(string $key): void
    {
        $this->db->pdo()
            ->prepare('DELETE FROM rate_limits WHERE key_hash = ?')
            ->execute([$this->hash($key)]);
    }

    public function availableIn(string $key): int
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, NOW(), expires_at)
             FROM rate_limits WHERE key_hash = ? AND expires_at > NOW()'
        );
        $stmt->execute([$this->hash($key)]);
        $seconds = $stmt->fetchColumn();

        return $seconds !== false ? max(1, (int) $seconds) : 0;
    }

    private function hash(string $key): string
    {
        return hash('sha256', $key);
    }
}
