<?php
declare(strict_types=1);

namespace App\Database;

use mysqli;

final class RoomCatalogSchemaMigrator
{
    public function __construct(private mysqli $connection)
    {
    }

    public function migrate(): void
    {
        $columns = [
            'des_hab' => 'TEXT NULL',
            'img_hab' => 'VARCHAR(255) NULL',
            'car_hab' => 'TEXT NULL',
        ];

        foreach ($columns as $column => $definition) {
            if (!$this->columnExists($column)) {
                $this->connection->query("ALTER TABLE habitacion ADD COLUMN {$column} {$definition}");
            }
        }

        $this->connection->query(
            "UPDATE habitacion
             SET des_hab = obs_hab
             WHERE (des_hab IS NULL OR des_hab = '') AND obs_hab IS NOT NULL AND obs_hab <> ''"
        );
    }

    private function columnExists(string $column): bool
    {
        $statement = $this->connection->prepare(
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitacion' AND COLUMN_NAME = ? LIMIT 1"
        );
        $statement->bind_param('s', $column);
        $statement->execute();
        $statement->store_result();
        $exists = $statement->num_rows === 1;
        $statement->close();

        return $exists;
    }
}
