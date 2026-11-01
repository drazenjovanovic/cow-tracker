<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Role;
use App\Domain\User;
use App\Repository\UserRepositoryInterface;
use App\Service\Auth;

/**
 * Builds an Auth instance backed by ArraySession for use in middleware tests.
 * Uses a hand-rolled UserRepositoryInterface stub — no PHPUnit TestCase methods needed.
 */
final class AuthBuilder
{
    private ?User $loggedInUser = null;

    /** @var array<int, User> id → User, returned by findById */
    private array $usersById = [];

    /** @var array<string, User> email → User, returned by findByEmail */
    private array $usersByEmail = [];

    private ArraySession $session;

    public function __construct()
    {
        $this->session = new ArraySession();
    }

    public function withVerifiedUser(Role $role = Role::User): self
    {
        $clone = clone $this;
        $clone->loggedInUser = new User(
            1, 'Test', 'test@example.com', 'hash', $role,
            '2024-01-01 00:00:00', '2024-01-02 00:00:00',
        );

        return $clone;
    }

    public function withUnverifiedUser(): self
    {
        $clone = clone $this;
        $clone->loggedInUser = new User(
            1, 'Test', 'test@example.com', 'hash', Role::User,
            '2024-01-01 00:00:00', null,
        );

        return $clone;
    }

    /** Register a user so findByEmail / findById return it. */
    public function withEmailUser(User $user): self
    {
        $clone = clone $this;
        $clone->usersById[$user->id]       = $user;
        $clone->usersByEmail[$user->email] = $user;

        return $clone;
    }

    public function build(): Auth
    {
        $usersById    = $this->usersById;
        $usersByEmail = $this->usersByEmail;

        $repo = new class ($usersById, $usersByEmail) implements UserRepositoryInterface {
            /** @param array<int, User> $byId */
            /** @param array<string, User> $byEmail */
            public function __construct(
                private readonly array $byId,
                private readonly array $byEmail,
            ) {}

            public function findById(int $id): ?User
            {
                return $this->byId[$id] ?? null;
            }

            public function findByEmail(string $email): ?User
            {
                return $this->byEmail[$email] ?? null;
            }

            public function emailExists(string $email): bool { return false; }

            public function create(string $name, string $email, string $passwordHash, \App\Domain\Role $role = \App\Domain\Role::User): User
            {
                throw new \LogicException('Not implemented in stub');
            }

            public function updatePasswordHash(int $id, string $hash): void {}

            public function markEmailVerified(int $id): void {}

            public function countAdmins(): int { return 0; }

            public function listForSelect(): array { return []; }
        };

        $auth = new Auth($this->session, $repo);

        if ($this->loggedInUser !== null) {
            $auth->login($this->loggedInUser);
        }

        return $auth;
    }

    public function session(): ArraySession
    {
        return $this->session;
    }
}
