<?php

declare(strict_types=1);

return [
    'up' => [
        "CREATE TABLE password_resets (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            email      VARCHAR(255) NOT NULL,
            token_hash VARCHAR(64)  NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX idx_password_resets_email   (email),
            INDEX idx_password_resets_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS password_resets',
    ],
];