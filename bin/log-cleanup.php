#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Deletes daily log files older than N days.
 *
 * Usage:
 *   php bin/log-cleanup.php            # delete logs older than 30 days (default)
 *   php bin/log-cleanup.php --days=7   # delete logs older than 7 days
 *   php bin/log-cleanup.php --dry-run  # preview without deleting
 */

$basePath = dirname(__DIR__);
require $basePath . '/vendor/autoload.php';

$opts    = getopt('', ['days::', 'dry-run']);
$days    = (int) ($opts['days'] ?? 30);
$dryRun  = isset($opts['dry-run']);
$logDir  = $basePath . '/storage/logs';
$cutoff  = strtotime("-{$days} days");
$deleted = 0;

if ($days < 1) {
    fwrite(STDERR, "Error: --days must be at least 1\n");
    exit(1);
}

foreach (glob($logDir . '/app-*.log') as $file) {
    if (filemtime($file) < $cutoff) {
        if ($dryRun) {
            echo '[dry-run] would delete: ' . basename($file) . "\n";
        } else {
            unlink($file);
            echo 'Deleted: ' . basename($file) . "\n";
        }
        $deleted++;
    }
}

echo ($dryRun ? 'Would delete' : 'Deleted') . " {$deleted} file(s) older than {$days} days.\n";
