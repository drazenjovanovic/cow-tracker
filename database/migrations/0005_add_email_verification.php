<?php

declare(strict_types=1);

return [
    'up' => [
        'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL',
        // Grandfather in existing accounts so they are not locked out after this migration.
        'UPDATE users SET email_verified_at = created_at WHERE email_verified_at IS NULL',
        "CREATE TABLE email_verifications (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            email      VARCHAR(255) NOT NULL,
            token_hash VARCHAR(64)  NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_email_verifications_email   (email),
            INDEX idx_email_verifications_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS email_verifications',
        'ALTER TABLE users DROP COLUMN email_verified_at',
    ],
];
