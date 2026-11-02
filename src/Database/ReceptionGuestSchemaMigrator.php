<?php
declare(strict_types=1);

namespace App\Database;

use mysqli;
use RuntimeException;

final class ReceptionGuestSchemaMigrator
{
    public static function ensure(mysqli $connection): void
    {
        $columns = [];
        $result = $connection->query(
            "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, CHARACTER_SET_NAME, COLLATION_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'usuario'
               AND COLUMN_NAME IN ('corr_usu', 'tipo_doc_usu')"
        );
        if (!$result) {
            throw new RuntimeException('No se pudo comprobar el esquema de huéspedes.');
        }
        while ($column = $result->fetch_assoc()) {
            $columns[$column['COLUMN_NAME']] = $column;
        }
        $result->free();

        if (!isset($columns['tipo_doc_usu'])) {
            if (!$connection->query(
                "ALTER TABLE usuario ADD COLUMN tipo_doc_usu VARCHAR(10) NULL AFTER doc_usu"
            )) {
                throw new RuntimeException('No se pudo preparar el tipo de documento del huésped.');
            }
        }

        $emailColumn = $columns['corr_usu'] ?? null;
        if ($emailColumn === null) {
            throw new RuntimeException('No se encontró la columna de correo de huéspedes.');
        }
        if ($emailColumn['IS_NULLABLE'] !== 'YES') {
            $columnType = (string) $emailColumn['COLUMN_TYPE'];
            $characterSet = (string) ($emailColumn['CHARACTER_SET_NAME'] ?? '');
            $collation = (string) ($emailColumn['COLLATION_NAME'] ?? '');
            if (
                preg_match('/^(?:varchar|char)\(\d+\)$/i', $columnType) !== 1
                || preg_match('/^[a-zA-Z0-9_]+$/', $characterSet) !== 1
                || preg_match('/^[a-zA-Z0-9_]+$/', $collation) !== 1
            ) {
                throw new RuntimeException('La definición del correo de huéspedes no es compatible.');
            }
            if (!$connection->query(
                "ALTER TABLE usuario MODIFY corr_usu {$columnType} CHARACTER SET {$characterSet} COLLATE {$collation} NULL DEFAULT NULL"
            )) {
                throw new RuntimeException('No se pudo permitir huéspedes sin correo electrónico.');
            }
        }
    }
}
