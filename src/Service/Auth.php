<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\SessionInterface;
use App\Domain\User;
use App\Repository\UserRepositoryInterface;

final class Auth
{
    private const SESSION_KEY = 'user_id';

    private static ?string $dummyHash = null;

    private ?User $user = null;
    private bool $resolved = false;

    public function __construct(
        private readonly SessionInterface $session,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function user(): ?User
    {
        if (!$this->resolved) {
            $id = $this->session->get(self::SESSION_KEY);
            $this->user = is_int($id) ? $this->users->findById($id) : null;
            if ($this->user === null && $id !== null) {
                $this->session->remove(self::SESSION_KEY); // user was deleted
            }
            $this->resolved = true;
        }

        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /** Verifies credentials only; call login() to actually sign the user in. */
    public function attempt(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            // Burn comparable time so response timing doesn't reveal whether the email exists.
            self::$dummyHash ??= password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            password_verify($password, self::$dummyHash);

            return null;
        }
        if (!password_verify($password, $user->passwordHash)) {
            return null;
        }
        if (password_needs_rehash($user->passwordHash, PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($user->id, password_hash($password, PASSWORD_DEFAULT));
        }

        return $user;
    }

    public function login(User $user): void
    {
        $this->session->regenerate(); // prevents session fixation
        $this->session->set(self::SESSION_KEY, $user->id);
        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        $this->session->invalidate();
        $this->user = null;
        $this->resolved = true;
    }
}
