<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $post = [],
        public readonly array $server = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?? '/'));
        $path = '/' . trim($path, '/'); // "/login/" -> "/login", "" -> "/"

        return new self(
            strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            $path,
            $_GET,
            $_POST,
            $_SERVER,
        );
    }

    public function header(string $name): ?string
    {
        $value = $this->server['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null;

        return is_string($value) ? $value : null;
    }

    /** Client IP. X-Forwarded-For is deliberately ignored: it is spoofable unless a trusted proxy sets it. */
    public function ip(): ?string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? null;

        return is_string($ip) ? $ip : null;
    }

    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    public function isSecure(): bool
    {
        $https = $this->server['HTTPS'] ?? '';

        return (is_string($https) && $https !== '' && strtolower($https) !== 'off')
            || (int) ($this->server['SERVER_PORT'] ?? 0) === 443;
    }

    public function wantsJson(): bool
    {
        return str_contains((string) $this->header('Accept'), 'application/json');
    }

    public function postString(string $key): string
    {
        $value = $this->post[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
