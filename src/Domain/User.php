<?php

declare(strict_types=1);

namespace App\Domain;

final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly string $passwordHash,
        public readonly Role $role,
        public readonly string $createdAt,
        public readonly ?string $emailVerifiedAt = null,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) $row['id'],
            (string) $row['name'],
            (string) $row['email'],
            (string) $row['password_hash'],
            Role::from((string) $row['role']),
            (string) $row['created_at'],
            isset($row['email_verified_at']) ? ((string) $row['email_verified_at'] ?: null) : null,
        );
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }
}
