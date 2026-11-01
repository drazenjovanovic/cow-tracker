<?php

declare(strict_types=1);

return [
    'up' => [
        "CREATE TABLE users (
            id            INT          AUTO_INCREMENT PRIMARY KEY,
            name          VARCHAR(255) NOT NULL,
            email         VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role          ENUM('user', 'admin') NOT NULL DEFAULT 'user',
            created_at    DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS users',
    ],
];
