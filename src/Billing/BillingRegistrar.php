<?php
declare(strict_types=1);

namespace App\Billing;

use mysqli;

/**
 * Registra en las tablas `pagos` y `factura` todo lo que se cobra:
 * alojamiento, experiencias y room service.
 *
 *  - pagos.concepto separa el origen del pago ('alojamiento' | 'experiencia' | 'room_service').
 *    El saldo del alojamiento solo cuenta los pagos con concepto 'alojamiento'.
 *  - factura guarda el folio, el tipo, el método de pago y el detalle completo (JSON).
 *
 * Los métodos de registro nunca lanzan: si algo falla lo dejan en el log y devuelven null,
 * para que un problema de contabilidad no tumbe el cobro que ya se está haciendo.
 */
final class BillingRegistrar
{
    private static bool $verificado = false;

    public static function asegurarEsquema(mysqli $db): void
    {
        if (self::$verificado) {
            return;
        }
        self::$verificado = true;

        try {
            $columnaNueva = false;
            $columnas = [
                ['pagos', 'concepto', "ALTER TABLE `pagos` ADD COLUMN `concepto` varchar(20) NOT NULL DEFAULT 'alojamiento'"],
                ['factura', 'folio', 'ALTER TABLE `factura` ADD COLUMN `folio` varchar(40) DEFAULT NULL'],
                ['factura', 'tipo_fac', "ALTER TABLE `factura` ADD COLUMN `tipo_fac` varchar(20) NOT NULL DEFAULT 'alojamiento'"],
                ['factura', 'metodo_pago_fac', 'ALTER TABLE `factura` ADD COLUMN `metodo_pago_fac` varchar(30) DEFAULT NULL'],
                ['factura', 'detalle_fac', 'ALTER TABLE `factura` ADD COLUMN `detalle_fac` longtext DEFAULT NULL'],
            ];
            foreach ($columnas as [$tabla, $columna, $sql]) {
                if (!self::existeColumna($db, $tabla, $columna)) {
                    $db->query($sql);
                    $columnaNueva = $columnaNueva || $columna === 'folio';
                }
            }
            // La tabla original solo permitía UNA factura por reserva (índice único en cod_res_fac).
            // Una reserva puede tener varias (alojamiento, experiencias, room service): pasa a índice normal.
            if (self::indiceEsUnico($db, 'factura', 'cod_res_fac')) {
                $db->query('ALTER TABLE `factura` ADD KEY `idx_factura_reserva` (`cod_res_fac`), DROP INDEX `cod_res_fac`');
            }
            if (!self::existeIndice($db, 'factura', 'uq_factura_folio')) {
                $db->query('ALTER TABLE `factura` ADD UNIQUE KEY `uq_factura_folio` (`folio`)');
            }
            if ($columnaNueva) {
                self::completarHistorial($db);
            }
        } catch (\Throwable $error) {
            error_log('No se pudo preparar el esquema de facturación: ' . $error->getMessage());
        }
    }

    /** Registra un pago aprobado. Devuelve el id del pago, o null si no aplica o ya existía. */
    public static function registrarPago(
        mysqli $db,
        ?int $codRes,
        float $monto,
        string $metodo,
        string $referencia,
        string $concepto
    ): ?int {
        if ($codRes === null || $monto <= 0) {
            return null; // `pagos` exige una reserva: un cobro sin reserva solo deja factura.
        }
        try {
            $existe = $db->prepare('SELECT id_pago FROM pagos WHERE referencia_pago = ? LIMIT 1');
            $existe->bind_param('s', $referencia);
            $existe->execute();
            $existe->store_result();
            $yaRegistrado = $existe->num_rows > 0;
            $existe->close();
            if ($yaRegistrado) {
                return null;
            }

            $stmt = $db->prepare(
                "INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago, concepto)
                 SELECT cod_res, ?, ?, ?, 'Aprobado', ? FROM reservas WHERE cod_res = ?"
            );
            $stmt->bind_param('dsssi', $monto, $metodo, $referencia, $concepto, $codRes);
            $stmt->execute();
            $id = $stmt->affected_rows === 1 ? (int) $db->insert_id : null;
            $stmt->close();
            return $id;
        } catch (\Throwable $error) {
            error_log('No se pudo registrar el pago (' . $concepto . '): ' . $error->getMessage());
            return null;
        }
    }

    /** Registra la factura de un cobro. Devuelve cod_fac, o null si falló o el folio ya existía. */
    public static function registrarFactura(
        mysqli $db,
        string $folio,
        string $tipo,
        ?int $codRes,
        float $total,
        string $metodo,
        array $detalle
    ): ?int {
        try {
            $existe = $db->prepare('SELECT cod_fac FROM factura WHERE folio = ? LIMIT 1');
            $existe->bind_param('s', $folio);
            $existe->execute();
            $existe->store_result();
            $yaRegistrada = $existe->num_rows > 0;
            $existe->close();
            if ($yaRegistrada) {
                return null;
            }

            $json = json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $stmt = $db->prepare(
                "INSERT INTO factura (tot_fac, est_fac, cod_res_fac, folio, tipo_fac, metodo_pago_fac, detalle_fac)
                 VALUES (?, 'Pagada', (SELECT cod_res FROM reservas WHERE cod_res = ?), ?, ?, ?, ?)"
            );
            $stmt->bind_param('disss' . 's', $total, $codRes, $folio, $tipo, $metodo, $json);
            $stmt->execute();
            $id = (int) $db->insert_id;
            $stmt->close();
            return $id;
        } catch (\Throwable $error) {
            error_log('No se pudo registrar la factura ' . $folio . ': ' . $error->getMessage());
            return null;
        }
    }

    private static function existeColumna(mysqli $db, string $tabla, string $columna): bool
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1'
        );
        $stmt->bind_param('ss', $tabla, $columna);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    private static function indiceEsUnico(mysqli $db, string $tabla, string $indice): bool
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? AND NON_UNIQUE = 0 LIMIT 1'
        );
        $stmt->bind_param('ss', $tabla, $indice);
        $stmt->execute();
        $stmt->store_result();
        $unico = $stmt->num_rows > 0;
        $stmt->close();
        return $unico;
    }

    private static function existeIndice(mysqli $db, string $tabla, string $indice): bool
    {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1'
        );
        $stmt->bind_param('ss', $tabla, $indice);
        $stmt->execute();
        $stmt->store_result();
        $existe = $stmt->num_rows > 0;
        $stmt->close();
        return $existe;
    }

    /**
     * Primera instalación: pasa a `pagos` y `factura` lo que ya se había cobrado antes
     * (sin duplicar nada: cada fila se identifica por su referencia o su folio).
     */
    private static function completarHistorial(mysqli $db): void
    {
        $sentencias = [
            // Pagos de experiencias ya cobradas (solo las que tienen reserva)
            "INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago, fecha_pago, concepto)
             SELECT a.cod_res_agenda, a.monto_experiencia, COALESCE(a.metodo_pago_experiencia, 'Efectivo'),
                    CONCAT('EXP-', a.id_agenda), 'Aprobado', COALESCE(a.fecha_pago_experiencia, NOW()), 'experiencia'
             FROM agenda_actividad a INNER JOIN reservas r ON r.cod_res = a.cod_res_agenda
             WHERE a.estado_pago_experiencia = 'Pagada' AND a.monto_experiencia > 0
               AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.referencia_pago = CONCAT('EXP-', a.id_agenda))",

            // Pagos de room service ya cobrados (uno por factura)
            "INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago, fecha_pago, concepto)
             SELECT MIN(p.cod_res_pedido), SUM(p.total), MIN(p.metodo_pago), CONCAT('RS-', p.folio), 'Aprobado',
                    COALESCE(MIN(p.fecha_pago), NOW()), 'room_service'
             FROM room_service_pedidos p INNER JOIN reservas r ON r.cod_res = p.cod_res_pedido
             WHERE p.estado_pago = 'Pagado' AND p.estado <> 'Cancelado' AND p.folio IS NOT NULL AND p.total > 0
             GROUP BY p.folio
             HAVING NOT EXISTS (SELECT 1 FROM pagos x WHERE x.referencia_pago = CONCAT('RS-', p.folio))",

            // Facturas de alojamiento
            "INSERT INTO factura (fech_fac, tot_fac, est_fac, cod_res_fac, folio, tipo_fac, metodo_pago_fac)
             SELECT p.fecha_pago, p.monto, 'Pagada', p.cod_res_pago, CONCAT('FAC-', p.id_pago), 'alojamiento', p.metodo_pago
             FROM pagos p
             WHERE p.concepto = 'alojamiento' AND p.estado_pago = 'Aprobado'
               AND NOT EXISTS (SELECT 1 FROM factura f WHERE f.folio = CONCAT('FAC-', p.id_pago))",

            // Facturas de experiencias
            "INSERT INTO factura (fech_fac, tot_fac, est_fac, cod_res_fac, folio, tipo_fac, metodo_pago_fac)
             SELECT COALESCE(a.fecha_pago_experiencia, NOW()), a.monto_experiencia, 'Pagada',
                    (SELECT r.cod_res FROM reservas r WHERE r.cod_res = a.cod_res_agenda),
                    CONCAT('FAC-EXP-', a.id_agenda), 'experiencia', a.metodo_pago_experiencia
             FROM agenda_actividad a
             WHERE a.estado_pago_experiencia = 'Pagada' AND a.monto_experiencia > 0
               AND NOT EXISTS (SELECT 1 FROM factura f WHERE f.folio = CONCAT('FAC-EXP-', a.id_agenda))",

            // Facturas de room service (una por folio)
            "INSERT INTO factura (fech_fac, tot_fac, est_fac, cod_res_fac, folio, tipo_fac, metodo_pago_fac)
             SELECT COALESCE(MIN(p.fecha_pago), NOW()), SUM(p.total), 'Pagada',
                    (SELECT r.cod_res FROM reservas r WHERE r.cod_res = MIN(p.cod_res_pedido)),
                    p.folio, 'room_service', MIN(p.metodo_pago)
             FROM room_service_pedidos p
             WHERE p.estado_pago = 'Pagado' AND p.estado <> 'Cancelado' AND p.folio IS NOT NULL
             GROUP BY p.folio
             HAVING NOT EXISTS (SELECT 1 FROM factura f WHERE f.folio = p.folio)",
        ];
        foreach ($sentencias as $sql) {
            try {
                $db->query($sql);
            } catch (\Throwable $error) {
                // Si falta alguna tabla (p. ej. room service aún sin instalar) se omite ese paso.
                error_log('Historial de facturación: ' . $error->getMessage());
            }
        }
    }
}
