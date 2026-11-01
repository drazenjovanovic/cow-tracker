<?php

declare(strict_types=1);

namespace App\Service;

use App\Core\MailerInterface;
use App\Domain\User;
use App\Repository\EmailVerificationRepository;
use App\Repository\UserRepositoryInterface;

final class EmailVerificationService
{
    private const EXPIRY_HOURS = 24;

    public function __construct(
        private readonly EmailVerificationRepository $verifications,
        private readonly UserRepositoryInterface $users,
        private readonly MailerInterface $mailer,
        private readonly string $appUrl,
    ) {
    }

    public function sendVerificationEmail(User $user): void
    {
        $this->verifications->deleteByEmail($user->email);

        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + self::EXPIRY_HOURS * 3600);
        $this->verifications->create($user->email, $this->hash($token), $expiresAt);

        $link = rtrim($this->appUrl, '/') . '/verify-email/confirm?token=' . $token;
        $body = implode("\n", [
            "Hello {$user->name},",
            '',
            'Please verify your email address by clicking the link below:',
            '',
            $link,
            '',
            'This link expires in ' . self::EXPIRY_HOURS . ' hours.',
            '',
            'If you did not create an account, you can ignore this email.',
        ]);

        $this->mailer->send($user->email, 'Verify your email address', $body);
    }

    public function verify(string $token): ?string
    {
        $row = $this->verifications->findValid($this->hash($token));
        if ($row === null) {
            return null;
        }

        $user = $this->users->findByEmail($row['email']);
        if ($user === null) {
            return null;
        }

        $this->users->markEmailVerified($user->id);
        $this->verifications->deleteByTokenHash($this->hash($token));

        return $row['email'];
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
