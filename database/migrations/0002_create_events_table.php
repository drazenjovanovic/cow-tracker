<?php

declare(strict_types=1);

return [
    'up' => [
        "CREATE TABLE events (
            id         INT  AUTO_INCREMENT PRIMARY KEY,
            user_id    INT  NULL,
            action     ENUM('login', 'logout', 'registration', 'view-page', 'button-click') NOT NULL,
            target     ENUM('page_a', 'page_b', 'buy_cow', 'download') NULL,
            ip         VARCHAR(45)  NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'CREATE INDEX idx_events_created_at    ON events (created_at)',
        'CREATE INDEX idx_events_user_created  ON events (user_id, created_at)',
        'CREATE INDEX idx_events_action_target ON events (action, target, created_at)',
    ],
    'down' => [
        'DROP TABLE IF EXISTS events',
    ],
];
