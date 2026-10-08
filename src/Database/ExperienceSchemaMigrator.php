<?php
declare(strict_types=1);

namespace App\Database;

use mysqli;
use RuntimeException;

final class ExperienceSchemaMigrator
{
    public function __construct(private mysqli $connection)
    {
    }

    public function migrate(): void
    {
        $this->migrateExperiences();
        $this->migrateActivityAgenda();
    }

    /**
     * Indica si faltan columnas que el panel de experiencias necesita (por ejemplo, en una base
     * exportada antes de que existieran los precios, horarios y cobros de experiencias).
     */
    public function requiereMigracion(): bool
    {
        $esperadas = [
            'experiencias' => ['imagen', 'opcion_1', 'precio_opcion_1', 'horarios_json'],
            'agenda_actividad' => [
                'opcion_actividad', 'selecciones_personas_json', 'estado_pago_experiencia',
                'cod_res_agenda', 'monto_experiencia', 'precios_personas_json',
            ],
        ];
        $resultado = $this->connection->query(
            "SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('experiencias', 'agenda_actividad')"
        );
        if (!$resultado) {
            throw new RuntimeException('No se pudo comprobar la estructura de experiencias.');
        }
        $existentes = [];
        while ($fila = $resultado->fetch_assoc()) {
            $existentes[$fila['TABLE_NAME']][$fila['COLUMN_NAME']] = true;
        }
        $resultado->free();

        foreach ($esperadas as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                if (!isset($existentes[$tabla][$columna])) {
                    return true;
                }
            }
        }
        return false;
    }

    private function migrateExperiences(): void
    {
        $this->connection->query(
            "CREATE TABLE IF NOT EXISTS experiencias (
                id INT AUTO_INCREMENT PRIMARY KEY,
                categoria VARCHAR(50) NOT NULL,
                nombre VARCHAR(150) NOT NULL,
                descripcion TEXT NOT NULL,
                fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $columns = [
            'imagen' => 'VARCHAR(255) NULL',
            'opcion_1' => "VARCHAR(100) NOT NULL DEFAULT ''",
            'opcion_2' => "VARCHAR(100) NOT NULL DEFAULT ''",
            'opcion_3' => "VARCHAR(100) NOT NULL DEFAULT ''",
            'precio_opcion_1' => 'DECIMAL(12,2) NULL',
            'precio_opcion_2' => 'DECIMAL(12,2) NULL',
            'precio_opcion_3' => 'DECIMAL(12,2) NULL',
            'horarios_json' => 'TEXT NULL',
        ];
        foreach ($columns as $column => $definition) {
            $this->addColumnIfMissing('experiencias', $column, $definition);
        }
    }

    private function migrateActivityAgenda(): void
    {
        $this->connection->query(
            "CREATE TABLE IF NOT EXISTS agenda_actividad (
                id_agenda BIGINT AUTO_INCREMENT PRIMARY KEY,
                actividad VARCHAR(150) NOT NULL,
                opcion_actividad VARCHAR(100) NOT NULL DEFAULT '',
                selecciones_personas_json TEXT NULL,
                fecha_agenda DATE NULL,
                hora_agenda TIME NULL,
                nombre_contacto VARCHAR(140) NOT NULL,
                correo_contacto VARCHAR(140) NOT NULL,
                id_usu_agenda BIGINT NULL,
                estado_agenda VARCHAR(30) DEFAULT 'Pendiente',
                estado_pago_experiencia VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
                fecha_pago_experiencia DATETIME NULL,
                metodo_pago_experiencia VARCHAR(30) NULL,
                cod_res_agenda BIGINT NULL,
                creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        $this->addColumnIfMissing('agenda_actividad', 'cod_res_agenda', 'BIGINT NULL');
        $columns = [
            'opcion_actividad' => "VARCHAR(100) NOT NULL DEFAULT '' AFTER actividad",
            'selecciones_personas_json' => 'TEXT NULL AFTER opcion_actividad',
            'id_usu_agenda' => 'BIGINT NULL',
            'estado_agenda' => "VARCHAR(30) DEFAULT 'Pendiente'",
            'estado_pago_experiencia' => "VARCHAR(20) NOT NULL DEFAULT 'Pendiente'",
            'fecha_pago_experiencia' => 'DATETIME NULL',
            'metodo_pago_experiencia' => 'VARCHAR(30) NULL',
            'monto_experiencia' => 'DECIMAL(12,2) NULL',
            'precios_personas_json' => 'TEXT NULL',
        ];
        foreach ($columns as $column => $definition) {
            $this->addColumnIfMissing('agenda_actividad', $column, $definition);
        }

        $this->ensureNullableColumn('agenda_actividad', 'fecha_agenda', 'DATE NULL');
        $this->ensureNullableColumn('agenda_actividad', 'hora_agenda', 'TIME NULL');
        $this->ensureIndex('agenda_actividad', 'idx_agenda_reserva', 'cod_res_agenda');

        $this->connection->query(
            "UPDATE agenda_actividad a
             SET a.cod_res_agenda = (
                 SELECT r.cod_res
                 FROM reservas r
                 WHERE r.id_usu_res = a.id_usu_agenda
                   AND r.est_res NOT IN ('Cancelada', 'Cancelado')
                   AND DATE(r.fec_ent_res) <= a.fecha_agenda
                   AND DATE(r.fec_sal_res) > a.fecha_agenda
                 ORDER BY r.fec_ent_res DESC, r.cod_res DESC
                 LIMIT 1
             )
             WHERE a.cod_res_agenda IS NULL AND a.id_usu_agenda IS NOT NULL"
        );

        $activityType = $this->columnType('agenda_actividad', 'actividad');
        if (preg_match('/^varchar\((\d+)\)$/i', $activityType, $match) && (int) $match[1] < 150) {
            $this->connection->query(
                'ALTER TABLE agenda_actividad MODIFY actividad VARCHAR(150) NOT NULL'
            );
        }
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): bool
    {
        if ($this->hasColumn($table, $column)) {
            return false;
        }

        $this->connection->query(
            sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s', $table, $column, $definition)
        );
        return true;
    }

    private function ensureNullableColumn(string $table, string $column, string $type): void
    {
        if ($this->columnAllowsNull($table, $column)) {
            return;
        }

        $this->connection->query(
            sprintf('ALTER TABLE `%s` MODIFY `%s` %s', $table, $column, $type)
        );
    }

    private function ensureIndex(string $table, string $index, string $column): void
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1'
        );
        if (!$statement) {
            throw new RuntimeException('No se pudo comprobar el índice de la agenda de experiencias.');
        }
        $statement->bind_param('ss', $table, $index);
        $statement->execute();
        $statement->store_result();
        $exists = $statement->num_rows > 0;
        $statement->close();
        if (!$exists) {
            $this->connection->query(
                sprintf('CREATE INDEX `%s` ON `%s` (`%s`)', $index, $table, $column)
            );
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->columnMetadata($table, $column) !== null;
    }

    private function columnAllowsNull(string $table, string $column): bool
    {
        $metadata = $this->columnMetadata($table, $column);
        return $metadata !== null && $metadata['IS_NULLABLE'] === 'YES';
    }

    private function columnType(string $table, string $column): string
    {
        $metadata = $this->columnMetadata($table, $column);
        return (string) ($metadata['COLUMN_TYPE'] ?? '');
    }

    private function columnMetadata(string $table, string $column): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
        );
        if (!$statement) {
            throw new RuntimeException('No se pudo comprobar la estructura de la base de datos.');
        }
        $statement->bind_param('ss', $table, $column);
        $statement->execute();
        $result = $statement->get_result();
        $metadata = $result->fetch_assoc() ?: null;
        $statement->close();
        return $metadata;
    }
}