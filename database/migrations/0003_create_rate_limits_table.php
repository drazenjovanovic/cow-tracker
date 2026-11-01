<?php

declare(strict_types=1);

return [
    'up' => [
        "CREATE TABLE rate_limits (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            key_hash   VARCHAR(64)  NOT NULL UNIQUE,
            attempts   INT          NOT NULL DEFAULT 1,
            expires_at DATETIME     NOT NULL,
            INDEX idx_rate_limits_expires (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ],
    'down' => [
        'DROP TABLE IF EXISTS rate_limits',
    ],
];
