#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * One-shot, idempotent project setup:
 *   - creates the storage directories
 *   - creates / updates the database schema
 *   - creates a harmless placeholder installer.exe if none exists
 *   - creates the first admin account
 *
 * Usage: php bin/setup.php [--admin-email=you@example.com] [--admin-name="Your Name"] [--admin-password=secret123]
 */

use App\Core\Config;
use App\Core\Config\DatabaseConfig;
use App\Core\Database;
use App\Core\Migrator;
use App\Domain\Role;
use App\Repository\UserRepository;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

$base = dirname(__DIR__);
require $base . '/src/bootstrap.php';

$config = Config::load($base);
date_default_timezone_set($config->string('app.timezone', 'UTC'));

// 1. Directories ---------------------------------------------------------------------------------
foreach ([$config->string('session.path'), dirname($config->string('download.file'))] as $dir) {
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, "Cannot create directory: {$dir}\n");
        exit(1);
    }
}

// 2. Migrations ----------------------------------------------------------------------------------
$db = new Database(DatabaseConfig::fromConfig($config));
(new Migrator($db))->migrate($base . '/database/migrations');

// 3. Placeholder download ------------------------------------------------------------------------
$download = $config->string('download.file');
if (!is_file($download)) {
    file_put_contents($download, placeholderExe());
    echo "✔ Created placeholder download: {$download}\n  (replace it with any real .exe you want to serve)\n";
}

// 4. First admin ---------------------------------------------------------------------------------
$users = new UserRepository($db);
$opts = getopt('', ['admin-email::', 'admin-name::', 'admin-password::']);

$email = strtolower(trim((string) ($opts['admin-email'] ?? '')));
if ($email === '' && $users->countAdmins() === 0) {
    $email = 'admin@example.com';
}

if ($email !== '') {
    if ($users->emailExists($email)) {
        echo "• Account {$email} already exists, skipping admin creation.\n";
    } else {
        $password = (string) ($opts['admin-password'] ?? '');
        $generated = $password === '';
        if ($generated) {
            $password = bin2hex(random_bytes(8));
        } elseif (strlen($password) < 8 || strlen($password) > 72) {
            fwrite(STDERR, "Admin password must be 8 to 72 characters long.\n");
            exit(1);
        }

        $name = trim((string) ($opts['admin-name'] ?? '')) ?: 'Administrator';
        $user = $users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), Role::Admin);
        $users->markEmailVerified($user->id);

        echo "✔ Admin account created\n  email:    {$email}\n";
        echo $generated ? "  password: {$password}   (generated - shown only once)\n" : "  password: (the one you provided)\n";
    }
} else {
    echo "• An admin account already exists.\n";
}

echo "\nDone. Open http://localhost:8000\n";

/** A tiny, valid 16-bit DOS "MZ" executable that only prints a message. Safe stand-in for a real installer. */
function placeholderExe(): string
{
    $code = "\x0E\x1F\xBA\x0E\x00\xB4\x09\xCD\x21\xB8\x01\x4C\xCD\x21"; // push cs; pop ds; mov dx,msg; mov ah,9; int 21h; mov ax,4C01h; int 21h
    $message = "This is a placeholder installer. Replace storage/downloads/installer.exe with your real file.\r\r\n" . '$';
    $body = $code . $message;

    $size = 64 + strlen($body);
    $header = pack(
        'v14',
        0x5A4D,              // "MZ"
        $size % 512,         // bytes in last page
        intdiv($size + 511, 512), // pages in file
        0,                   // relocations
        4,                   // header size in paragraphs (64 bytes)
        0,                   // min extra paragraphs
        0xFFFF,              // max extra paragraphs
        0,                   // initial SS
        0x00B8,              // initial SP
        0,                   // checksum
        0,                   // initial IP
        0,                   // initial CS
        0x0040,              // relocation table offset
        0,                   // overlay number
    );

    return str_pad($header, 64, "\0") . $body;
}
