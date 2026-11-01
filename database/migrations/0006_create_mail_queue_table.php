<?php

declare(strict_types=1);

return [
    'up' => [
        "CREATE TABLE mail_queue (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            to_email     VARCHAR(190) NOT NULL,
            subject      VARCHAR(255) NOT NULL,
            body         TEXT         NOT NULL,
            attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
            available_at DATETIME     NOT NULL,
            sent_at      DATETIME     NULL,
            failed_at    DATETIME     NULL,
            error        VARCHAR(500) NULL,
            created_at   DATETIME     NOT NULL,
            INDEX idx_mail_queue_pending (available_at, sent_at, failed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ],
    'down' => [
        'DROP TABLE IF EXISTS mail_queue',
    ],
];
