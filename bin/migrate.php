#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Database migration runner.
 *
 * Usage:
 *   php bin/migrate.php              # run all pending migrations
 *   php bin/migrate.php --status     # show migration status
 *   php bin/migrate.php --rollback   # rollback the last batch
 */

use App\Core\Config;
use App\Core\Config\DatabaseConfig;
use App\Core\Database;
use App\Core\Migrator;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$base = dirname(__DIR__);
require $base . '/src/bootstrap.php';

$config = Config::load($base);
date_default_timezone_set($config->string('app.timezone', 'UTC'));

$db       = new Database(DatabaseConfig::fromConfig($config));
$migrator = new Migrator($db);
$path     = $base . '/database/migrations';

$opts = getopt('', ['status', 'rollback']);

if (isset($opts['status'])) {
    $migrator->status($path);
} elseif (isset($opts['rollback'])) {
    $migrator->rollback($path);
} else {
    $migrator->migrate($path);
}
