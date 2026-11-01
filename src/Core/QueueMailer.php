<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Stores outgoing emails in the mail_queue table instead of sending them
 * immediately. A separate worker process (bin/mail-worker.php) picks them up.
 */
final class QueueMailer implements MailerInterface
{
    public function __construct(private readonly Database $db)
    {
    }

    public function send(string $to, string $subject, string $body): void
    {
        $now = date('Y-m-d H:i:s');

        $this->db->execute(
            'INSERT INTO mail_queue (to_email, subject, body, available_at, created_at)
             VALUES (:to, :subject, :body, :now, :now2)',
            ['to' => $to, 'subject' => $subject, 'body' => $body, 'now' => $now, 'now2' => $now],
        );
    }
}
