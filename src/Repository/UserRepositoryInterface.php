<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\Role;
use App\Domain\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function emailExists(string $email): bool;

    /** @throws EmailAlreadyExists */
    public function create(string $name, string $email, string $passwordHash, Role $role = Role::User): User;

    public function updatePasswordHash(int $id, string $hash): void;

    public function markEmailVerified(int $id): void;

    public function countAdmins(): int;

    /** @return list<array{id: int, name: string, email: string}> */
    public function listForSelect(): array;
}
