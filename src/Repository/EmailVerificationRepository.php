<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class EmailVerificationRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function create(string $email, string $tokenHash, string $expiresAt): void
    {
        $this->db->execute(
            'INSERT INTO email_verifications (email, token_hash, expires_at, created_at)
             VALUES (:email, :token_hash, :expires_at, :created_at)',
            ['email' => $email, 'token_hash' => $tokenHash, 'expires_at' => $expiresAt, 'created_at' => date('Y-m-d H:i:s')],
        );
    }

    /** @return array{email: string}|null */
    public function findValid(string $tokenHash): ?array
    {
        $row = $this->db->execute(
            'SELECT email FROM email_verifications WHERE token_hash = :hash AND expires_at > NOW()',
            ['hash' => $tokenHash],
        )->fetch();

        return $row !== false ? $row : null;
    }

    public function deleteByEmail(string $email): void
    {
        $this->db->execute('DELETE FROM email_verifications WHERE email = :email', ['email' => $email]);
    }

    public function deleteByTokenHash(string $tokenHash): void
    {
        $this->db->execute('DELETE FROM email_verifications WHERE token_hash = :hash', ['hash' => $tokenHash]);
    }
}
