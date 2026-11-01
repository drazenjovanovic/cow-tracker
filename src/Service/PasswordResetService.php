<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\MailerInterface;
use App\Repository\PasswordResetRepository;
use App\Repository\UserRepositoryInterface;

final class PasswordResetService
{
    private const EXPIRY_MINUTES = 60;

    public function __construct(
        private readonly PasswordResetRepository $resets,
        private readonly UserRepositoryInterface $users,
        private readonly MailerInterface $mailer,
        private readonly string $appUrl,
    ) {
    }

    public function sendResetLink(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return; // don't reveal whether the email exists
        }

        $this->resets->deleteByEmail($email); // remove any previous token

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::EXPIRY_MINUTES * 60);
        $this->resets->create($email, $this->hash($token), $expiresAt);

        $link = rtrim($this->appUrl, '/') . '/reset-password?token=' . $token;
        $body = implode("\n", [
            "Hello {$user->name},",
            '',
            'You requested a password reset. Click the link below to set a new password:',
            '',
            $link,
            '',
            'This link expires in ' . self::EXPIRY_MINUTES . ' minutes.',
            '',
            'If you did not request a password reset, you can ignore this email.',
        ]);

        $this->mailer->send($email, 'Password Reset', $body);
    }

    public function validateToken(string $token): ?string
    {
        $row = $this->resets->findValid($this->hash($token));

        return $row !== null ? $row['email'] : null;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $email = $this->validateToken($token);
        if ($email === null) {
            return false;
        }

        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return false;
        }

        $this->users->updatePasswordHash($user->id, password_hash($newPassword, PASSWORD_DEFAULT));
        $this->resets->deleteByTokenHash($this->hash($token));

        return true;
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}