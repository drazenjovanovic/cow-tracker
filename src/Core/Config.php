<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /** @param array<string, mixed> $items */
    public function __construct(private readonly array $items)
    {
    }

    public static function load(string $basePath): self
    {
        /** @var array<string, mixed> $items */
        $items = require $basePath . '/config/config.php';

        return new self($items);
    }

    /** Dot notation lookup, e.g. get('db.path'). */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_string($value) ? $value : $default;
    }
}
