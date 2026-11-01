<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Config\LogConfig;

/**
 * Writes structured log entries to STDOUT.
 * JSON lines when piped/redirected (Docker, CI); human-readable when attached to a TTY.
 */
final class Logger implements LoggerInterface
{
    private const LEVELS = ['debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3];

    /** @var array<string, mixed> */
    private array $baseContext = [];

    public function __construct(private readonly LogConfig $config)
    {
        if (!is_dir($config->dir)) {
            mkdir($config->dir, 0755, true);
        }
    }

    /** @param array<string, mixed> $context */
    public function withContext(array $context): void
    {
        $this->baseContext = array_merge($this->baseContext, $context);
    }

    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        $this->write('DEBUG', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->write('WARNING', $message, $context);
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /** @param array<string, mixed> $context */
    private function write(string $level, string $message, array $context): void
    {
        if ((self::LEVELS[strtolower($level)] ?? 0) < (self::LEVELS[$this->config->level] ?? 1)) {
            return;
        }

        $merged = array_merge($this->baseContext, $context);

        $line = $this->isTty()
            ? $this->formatHuman($level, $message, $merged)
            : $this->formatJson($level, $message, $merged);

        file_put_contents('php://stdout', $line . "\n");

        if ($this->config->dir !== '') {
            $file = $this->config->dir . '/app-' . date('Y-m-d') . '.log';
            file_put_contents($file, $this->formatJson($level, $message, $merged) . "\n", FILE_APPEND);
        }
    }

    /** @param array<string, mixed> $context */
    private function formatJson(string $level, string $message, array $context): string
    {
        $entry = ['time' => date('Y-m-d H:i:s'), 'level' => $level, 'message' => $message];
        if ($context !== []) {
            $entry['context'] = $context;
        }

        return (string) json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string, mixed> $context */
    private function formatHuman(string $level, string $message, array $context): string
    {
        $colors = ['DEBUG' => "\033[37m", 'INFO' => "\033[32m", 'WARNING' => "\033[33m", 'ERROR' => "\033[31m"];
        $reset  = "\033[0m";
        $color  = $colors[$level] ?? '';
        $ctx    = $context !== [] ? '  ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';

        return sprintf('%s[%s] %-7s %s%s%s', $color, date('H:i:s'), $level, $message, $ctx, $reset);
    }

    private function isTty(): bool
    {
        static $result = null;
        if ($result === null) {
            $h      = fopen('php://stdout', 'wb');
            $result = $h !== false && function_exists('stream_isatty') && stream_isatty($h);
            if ($h !== false) {
                fclose($h);
            }
        }
        return $result;
    }
}
