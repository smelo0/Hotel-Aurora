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

    private static bool $offersReady = false;

    /**
     * Agrega a `habitacion` las columnas de oferta (precio y rango de fechas opcional).
     * Al crearlas por primera vez, las habitaciones sencillas que guardaban 50.000 por la antigua regla de
     * "1 persona" pasan a 80.000, que era el precio que ya se mostraba y cobraba.
     */
    public static function ensureOffers(mysqli $connection): void
    {
        if (self::$offersReady) {
            return;
        }
        $result = $connection->query(
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'habitacion' AND COLUMN_NAME = 'oferta_precio' LIMIT 1"
        );
        if (!$result) {
            throw new RuntimeException('No se pudo comprobar el esquema de ofertas.');
        }
        $exists = $result->num_rows > 0;
        $result->free();

        if (!$exists) {
            if (!$connection->query(
                'ALTER TABLE `habitacion`
                 ADD COLUMN `oferta_precio` DECIMAL(10,2) DEFAULT NULL,
                 ADD COLUMN `oferta_inicio` DATE DEFAULT NULL,
                 ADD COLUMN `oferta_fin` DATE DEFAULT NULL'
            )) {
                throw new RuntimeException('No se pudo preparar las ofertas de habitaciones.');
            }
            $connection->query(
                "UPDATE `habitacion` SET `pre_hab` = 80000.00
                 WHERE `pre_hab` = 50000.00 AND LOWER(`tipo_hab`) REGEXP 'sencilla|simple|individual'"
            );
        }
        self::$offersReady = true;
    }
}