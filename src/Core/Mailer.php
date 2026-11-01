<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Config\MailConfig;
use DateTimeImmutable;

final class Mailer implements MailerInterface
{
    public function __construct(private readonly MailConfig $config)
    {
    }

    public function send(string $to, string $subject, string $body): void
    {
        match ($this->config->driver) {
            'smtp'  => $this->sendSmtp($to, $subject, $body),
            'mail'  => $this->sendMail($to, $subject, $body),
            default => $this->log($to, $subject, $body),
        };
    }

    private function sendSmtp(string $to, string $subject, string $body): void
    {
        $address = $this->config->port === 465 ? "ssl://{$this->config->host}" : $this->config->host;
        $socket  = @fsockopen($address, $this->config->port, $errno, $errstr, 10);

        if ($socket === false) {
            throw new \RuntimeException("SMTP connection failed ({$this->config->host}:{$this->config->port}): {$errstr}");
        }

        try {
            $this->expect($socket, 220);

            $this->send_($socket, 'EHLO localhost');
            $greeting = $this->read($socket);

            // Upgrade to TLS for non-SSL ports when server supports it
            if ($this->config->port !== 465 && str_contains($greeting, 'STARTTLS')) {
                $this->send_($socket, 'STARTTLS');
                $this->expect($socket, 220);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $this->send_($socket, 'EHLO localhost');
                $this->read($socket);
            }

            $this->send_($socket, 'AUTH LOGIN');
            $this->expect($socket, 334);
            $this->send_($socket, base64_encode($this->config->username));
            $this->expect($socket, 334);
            $this->send_($socket, base64_encode($this->config->password));
            $this->expect($socket, 235);

            $this->send_($socket, "MAIL FROM:<{$this->config->from}>");
            $this->expect($socket, 250);
            $this->send_($socket, "RCPT TO:<{$to}>");
            $this->expect($socket, 250);

            $this->send_($socket, 'DATA');
            $this->expect($socket, 354);

            $headers = implode("\r\n", [
                "From: {$this->config->fromName} <{$this->config->from}>",
                "To: {$to}",
                "Subject: {$subject}",
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ]);

            // Dot-stuff lines starting with '.' per RFC 5321
            $safeBody = preg_replace('/^\./m', '..', $body) ?? $body;

            fwrite($socket, $headers . "\r\n\r\n" . $safeBody . "\r\n.\r\n");
            $this->expect($socket, 250);

            $this->send_($socket, 'QUIT');
        } finally {
            fclose($socket);
        }
    }

    private function sendMail(string $to, string $subject, string $body): void
    {
        $headers = implode("\r\n", [
            "From: {$this->config->fromName} <{$this->config->from}>",
            'Content-Type: text/plain; charset=UTF-8',
            'MIME-Version: 1.0',
        ]);

        mail($to, $subject, $body, $headers);
    }

    private function log(string $to, string $subject, string $body): void
    {
        $date  = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $entry = implode("\n", [
            str_repeat('=', 80),
            "Date:    {$date}",
            "To:      {$to}",
            "Subject: {$subject}",
            '',
            $body,
            '',
        ]) . "\n";

        $dir = dirname($this->config->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($this->config->logPath, $entry, FILE_APPEND | LOCK_EX);
    }

    /** @param resource $socket */
    private function send_(mixed $socket, string $data): void
    {
        fwrite($socket, $data . "\r\n");
    }

    /** @param resource $socket */
    private function read(mixed $socket): string
    {
        $response = '';
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break; // last line of a multi-line response ends with space after code
            }
        }
        return $response;
    }

    /** @param resource $socket */
    private function expect(mixed $socket, int $code): void
    {
        $response = $this->read($socket);
        if ((int) substr($response, 0, 3) !== $code) {
            throw new \RuntimeException("SMTP error (expected {$code}): " . trim($response));
        }
    }
}