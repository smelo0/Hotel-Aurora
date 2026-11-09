<?php
declare(strict_types=1);

namespace App\Reserva;

use mysqli;
use RuntimeException;

final class RoomRateSettings
{
    // La clave se conserva con su nombre original para no perder el valor ya guardado en la base de datos.
    private const CONFIG_KEY = 'suplemento_nino_adicional';
    private static bool $schemaReady = false;

    public static function additionalPersonSupplement(mysqli $connection): float
    {
        self::ensureSchema($connection);

        $stmt = $connection->prepare('SELECT valor FROM tarifa_hotel_configuracion WHERE clave = ? LIMIT 1');
        if (!$stmt) {
            throw new RuntimeException('No se pudo consultar el cargo por persona adicional: ' . $connection->error);
        }
        $key = self::CONFIG_KEY;
        $stmt->bind_param('s', $key);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('No se pudo consultar el cargo por persona adicional: ' . $error);
        }
        $stmt->bind_result($value);
        if (!$stmt->fetch()) {
            $stmt->close();
            throw new RuntimeException('No se encontró el cargo por persona adicional.');
        }
        $stmt->close();

        return (float) $value;
    }

    public static function setAdditionalPersonSupplement(mysqli $connection, float $value): void
    {
        if (!is_finite($value) || $value < 0 || $value > 99999999) {
            throw new RuntimeException('El cargo por persona adicional debe estar entre 0 y 99.999.999 pesos.');
        }
        self::ensureSchema($connection);

        $stmt = $connection->prepare(
            'INSERT INTO tarifa_hotel_configuracion (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo guardar el cargo por persona adicional: ' . $connection->error);
        }
        $key = self::CONFIG_KEY;
        $stmt->bind_param('sd', $key, $value);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('No se pudo guardar el cargo por persona adicional: ' . $error);
        }
        $stmt->close();
    }

    private static function ensureSchema(mysqli $connection): void
    {
        if (self::$schemaReady) {
            return;
        }

        if (!$connection->query(
            "CREATE TABLE IF NOT EXISTS tarifa_hotel_configuracion (
                clave VARCHAR(60) NOT NULL,
                valor DECIMAL(10,2) NOT NULL,
                PRIMARY KEY (clave)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        )) {
            throw new RuntimeException('No se pudo preparar la configuración de tarifas: ' . $connection->error);
        }
        $key = self::CONFIG_KEY;
        $default = RoomRateCalculator::ADDITIONAL_PERSON_SUPPLEMENT;
        $stmt = $connection->prepare(
            'INSERT IGNORE INTO tarifa_hotel_configuracion (clave, valor) VALUES (?, ?)'
        );
        if (!$stmt) {
            throw new RuntimeException('No se pudo inicializar el cargo por persona adicional: ' . $connection->error);
        }
        $stmt->bind_param('sd', $key, $default);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('No se pudo inicializar el cargo por persona adicional: ' . $error);
        }
        $stmt->close();
        self::$schemaReady = true;
    }
}