<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string, string> $headers */
    private function __construct(
        public readonly int $status,
        private readonly array $headers,
        private readonly string $body = '',
        private readonly ?string $file = null,
    ) {
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($status, ['Content-Type' => 'text/html; charset=UTF-8'], $body);
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self($status, ['Location' => $location]);
    }

    public static function download(string $path, string $filename): self
    {
        $size = filesize($path);
        $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?? 'download';

        return new self(200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                $asciiName,
                rawurlencode($filename),
            ),
            'Content-Length' => (string) ($size === false ? 0 : $size),
        ], '', $path);
    }

    public function withHeader(string $name, string $value): self
    {
        return new self($this->status, [$name => $value] + $this->headers, $this->body, $this->file);
    }

    public function hasHeader(string $name): bool
    {
        return array_key_exists($name, $this->headers);
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        if ($this->file !== null) {
            // Release the session lock and drop output buffers before streaming a file.
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            readfile($this->file);

            return;
        }

        echo $this->body;
    }
}
