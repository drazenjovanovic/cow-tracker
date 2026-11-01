<?php

declare(strict_types=1);

namespace App\Core;

interface LoggerInterface
{
    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void;

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void;

    /** Merge persistent context into every subsequent log entry (e.g. request_id). */
    /** @param array<string, mixed> $context */
    public function withContext(array $context): void;
}
