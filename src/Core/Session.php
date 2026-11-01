<?php

declare(strict_types=1);

namespace App\Core;

final class Session implements SessionInterface
{
    private const MESSAGES = '_messages';

    public function __construct(
        private readonly string $savePath,
        private readonly string $name,
    ) {
    }

    public function start(bool $secure): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!is_dir($this->savePath) && !mkdir($this->savePath, 0770, true) && !is_dir($this->savePath)) {
            throw new \RuntimeException("Cannot create session directory: {$this->savePath}");
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_name($this->name);
        session_save_path($this->savePath);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Read a value once and remove it (flash data). */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);

        return $value;
    }

    public function addMessage(string $type, string $text): void
    {
        $_SESSION[self::MESSAGES][] = ['type' => $type, 'text' => $text];
    }

    /** @return list<array{type: string, text: string}> */
    public function pullMessages(): array
    {
        /** @var list<array{type: string, text: string}> $messages */
        $messages = $this->pull(self::MESSAGES, []);

        return $messages;
    }

    /** New session id, data kept. Call on privilege change (login) to prevent session fixation. */
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /** New session id and all data dropped (logout). */
    public function invalidate(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }
}
