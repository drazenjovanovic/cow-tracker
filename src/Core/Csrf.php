<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf';

    public function __construct(private readonly SessionInterface $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set(self::KEY, $token);
        }

        return $token;
    }

    public function isValid(?string $submitted): bool
    {
        return $submitted !== null && $submitted !== '' && hash_equals($this->token(), $submitted);
    }
}
