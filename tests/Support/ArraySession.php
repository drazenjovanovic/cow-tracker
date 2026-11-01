<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\SessionInterface;

/**
 * In-memory session for tests. No PHP session needed.
 */
final class ArraySession implements SessionInterface
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function start(bool $secure): void {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->data[$key] ?? $default;
        unset($this->data[$key]);

        return $value;
    }

    public function addMessage(string $type, string $text): void
    {
        $this->data['_messages'][] = ['type' => $type, 'text' => $text];
    }

    /** @return list<array{type: string, text: string}> */
    public function pullMessages(): array
    {
        /** @var list<array{type: string, text: string}> $messages */
        $messages = $this->pull('_messages', []);

        return $messages;
    }

    public function regenerate(): void {}

    public function invalidate(): void
    {
        $this->data = [];
    }
}
