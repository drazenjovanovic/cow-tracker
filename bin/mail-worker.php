#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Mail queue worker.
 *
 * Processes pending jobs from the mail_queue table and sends them via the
 * configured mail driver. Failed jobs are retried up to 3 times with
 * exponential backoff (5 min, 10 min, 20 min). After the third failure the
 * job is marked permanently failed and left in the table for inspection.
 *
 * Usage:
 *   php bin/mail-worker.php                 # process one batch of 10, then exit
 *   php bin/mail-worker.php --loop          # keep running, poll every 5 seconds
 *   php bin/mail-worker.php --batch=25      # process up to 25 jobs per run
 */

use App\Core\Config;
use App\Core\Config\DatabaseConfig;
use App\Core\Config\LogConfig;
use App\Core\Config\MailConfig;
use App\Core\Database;
use App\Core\Logger;
use App\Core\LoggerInterface;
use App\Core\Mailer;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$base = dirname(__DIR__);
require $base . '/src/bootstrap.php';

$opts      = getopt('', ['loop', 'batch:']);
$loop      = isset($opts['loop']);
$batchSize = max(1, (int) ($opts['batch'] ?? 10));

$config = Config::load($base);
date_default_timezone_set($config->string('app.timezone', 'UTC'));

$log    = new Logger(LogConfig::fromConfig($config, $base));
$db     = new Database(DatabaseConfig::fromConfig($config), $log);
// Always use the real mailer — QueueMailer is only for the web process.
$mailer = new Mailer(MailConfig::fromConfig($config));

const MAX_ATTEMPTS = 3;

function processBatch(Database $db, Mailer $mailer, LoggerInterface $log, int $batchSize): int
{
    $pdo = $db->pdo();

    $pdo->beginTransaction();

    try {
        // Lock pending rows so concurrent workers skip them (MySQL 8 SKIP LOCKED).
        $stmt = $pdo->prepare(
            'SELECT id, to_email, subject, body, attempts
               FROM mail_queue
              WHERE sent_at   IS NULL
                AND failed_at IS NULL
                AND attempts  < :max
                AND available_at <= NOW()
              ORDER BY available_at
              LIMIT :limit
              FOR UPDATE SKIP LOCKED'
        );
        $stmt->bindValue(':max',   MAX_ATTEMPTS, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $batchSize,   PDO::PARAM_INT);
        $stmt->execute();

        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($jobs === []) {
            $pdo->commit();

            return 0;
        }

        // Claim the batch immediately (increment attempts) so a worker restart
        // doesn't pick up a job that is currently being sent.
        $ids          = array_column($jobs, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE mail_queue SET attempts = attempts + 1 WHERE id IN ({$placeholders})")
            ->execute($ids);

        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    $sent = 0;
    foreach ($jobs as $job) {
        $id       = (int) $job['id'];
        $attempts = (int) $job['attempts'] + 1; // already incremented above

        try {
            $mailer->send($job['to_email'], $job['subject'], $job['body']);
            $pdo->prepare('UPDATE mail_queue SET sent_at = NOW() WHERE id = ?')->execute([$id]);
            $sent++;
            $log->info('Mail sent', ['job' => $id, 'to' => $job['to_email'], 'subject' => $job['subject']]);
        } catch (\Throwable $e) {
            $error = substr($e->getMessage(), 0, 500);
            if ($attempts >= MAX_ATTEMPTS) {
                $pdo->prepare('UPDATE mail_queue SET failed_at = NOW(), error = ? WHERE id = ?')
                    ->execute([$error, $id]);
                $log->error('Mail job failed permanently', ['job' => $id, 'attempts' => $attempts, 'error' => $error]);
            } else {
                // Exponential backoff: 5, 10, 20 minutes
                $delay = 5 * (2 ** ($attempts - 1));
                $pdo->prepare('UPDATE mail_queue SET available_at = DATE_ADD(NOW(), INTERVAL ? MINUTE), error = ? WHERE id = ?')
                    ->execute([$delay, $error, $id]);
                $log->warning('Mail job failed, will retry', ['job' => $id, 'attempt' => $attempts, 'max' => MAX_ATTEMPTS, 'retry_in_min' => $delay, 'error' => $error]);
            }
        }
    }

    return $sent;
}

if ($loop) {
    $log->info('Mail worker started', ['batch' => $batchSize, 'mode' => 'loop']);
    while (true) {
        try {
            processBatch($db, $mailer, $log, $batchSize);
        } catch (\Throwable $e) {
            $log->error('Worker batch failed', ['message' => $e->getMessage()]);
        }
        sleep(5);
    }
} else {
    $sent = processBatch($db, $mailer, $log, $batchSize);
    $log->info('Worker run complete', ['sent' => $sent]);
}
