<?php
declare(strict_types=1);

namespace App\Reserva;

use mysqli;
use RuntimeException;

final class RoomRateSchemaMigrator
{
    public static function ensure(mysqli $connection): void
    {
        $result = $connection->query(
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'detalle'
               AND COLUMN_NAME = 'precio_noche_det'
             LIMIT 1"
        );
        if (!$result) {
            throw new RuntimeException('No se pudo comprobar el esquema de tarifas de reserva.');
        }

        $exists = $result->num_rows > 0;
        $result->free();
        if ($exists) {
            return;
        }

        if (!$connection->query(
            'ALTER TABLE `detalle` ADD COLUMN `precio_noche_det` DECIMAL(12,2) DEFAULT NULL AFTER `cod_hab_det`'
        )) {
            throw new RuntimeException('No se pudo preparar el historial de tarifas de reserva.');
        }
    }
}
