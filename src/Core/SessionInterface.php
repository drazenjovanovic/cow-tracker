<?php

declare(strict_types=1);

namespace App\Core;

interface SessionInterface
{
    public function start(bool $secure): void;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value): void;

    public function remove(string $key): void;

    public function pull(string $key, mixed $default = null): mixed;

    public function addMessage(string $type, string $text): void;

    /** @return list<array{type: string, text: string}> */
    public function pullMessages(): array;

    public function regenerate(): void;

    public function invalidate(): void;
}
