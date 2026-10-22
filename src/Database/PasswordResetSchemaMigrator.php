<?php
declare(strict_types=1);

namespace App\Database;

use mysqli;

final class PasswordResetSchemaMigrator
{
    public function __construct(private mysqli $connection)
    {
    }

    public function migrate(): void
    {
        $this->connection->query(
            "CREATE TABLE IF NOT EXISTS password_resets (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY password_resets_token_unique (token),
                INDEX password_resets_email_created_at (email, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }
}
