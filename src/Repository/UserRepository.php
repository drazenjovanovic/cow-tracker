<?php

declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;
use App\Domain\Role;
use App\Domain\User;
use DateTimeImmutable;
use PDOException;

final class UserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?User
    {
        $row = $this->db->execute('SELECT * FROM users WHERE id = :id', ['id' => $id])->fetch();

        return $row !== false ? User::fromRow($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->db->execute('SELECT * FROM users WHERE email = :email', ['email' => $email])->fetch();

        return $row !== false ? User::fromRow($row) : null;
    }

    public function emailExists(string $email): bool
    {
        return $this->db->execute('SELECT 1 FROM users WHERE email = :email', ['email' => $email])->fetchColumn() !== false;
    }

    /** @throws EmailAlreadyExists */
    public function create(string $name, string $email, string $passwordHash, Role $role = Role::User): User
    {
        $createdAt = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->db->execute(
                'INSERT INTO users (name, email, password_hash, role, created_at)
                 VALUES (:name, :email, :hash, :role, :created_at)',
                ['name' => $name, 'email' => $email, 'hash' => $passwordHash, 'role' => $role->value, 'created_at' => $createdAt],
            );
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new EmailAlreadyExists($email, 0, $e);
            }
            throw $e;
        }

        return new User($this->db->lastInsertId(), $name, $email, $passwordHash, $role, $createdAt);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $this->db->execute('UPDATE users SET password_hash = :hash WHERE id = :id', ['hash' => $hash, 'id' => $id]);
    }

    public function markEmailVerified(int $id): void
    {
        $this->db->execute('UPDATE users SET email_verified_at = NOW() WHERE id = :id', ['id' => $id]);
    }

    public function countAdmins(): int
    {
        return (int) $this->db->execute("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    /** @return list<array{id: int, name: string, email: string}> */
    public function listForSelect(): array
    {
        $rows = $this->db->execute('SELECT id, name, email FROM users ORDER BY name, id')->fetchAll();

        return array_map(
            static fn (array $r): array => ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'email' => (string) $r['email']],
            $rows,
        );
    }
}
