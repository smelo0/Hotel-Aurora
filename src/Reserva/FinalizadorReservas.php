<?php
declare(strict_types=1);

namespace App\Reserva;

use mysqli;

/**
 * Pasa a "Finalizada" las reservas cuyo huésped ya se fue (la salida ya pasó) y que están pagadas:
 * tienen un pago de alojamiento aprobado y ningún pago pendiente. Las canceladas no se tocan.
 *
 * Se ejecuta al abrir los paneles y el portal del huésped (una vez por petición), no necesita cron.
 */
final class FinalizadorReservas
{
    private static bool $ejecutado = false;

    public static function ejecutar(mysqli $db): void
    {
        if (self::$ejecutado) {
            return;
        }
        self::$ejecutado = true;

        try {
            $db->query(
                "UPDATE reservas r
                 SET r.est_res = 'Finalizada'
                 WHERE LOWER(TRIM(COALESCE(r.est_res, ''))) IN ('pendiente', 'confirmada', 'en casa')
                   AND r.fec_sal_res < NOW()
                   AND EXISTS (
                       SELECT 1 FROM pagos pg
                       WHERE pg.cod_res_pago = r.cod_res AND pg.estado_pago = 'Aprobado' AND pg.concepto = 'alojamiento'
                   )
                   AND NOT EXISTS (
                       SELECT 1 FROM pagos pp
                       WHERE pp.cod_res_pago = r.cod_res AND pp.estado_pago = 'Pendiente'
                   )"
            );
        } catch (\Throwable $error) {
            error_log('No se pudieron finalizar las reservas vencidas: ' . $error->getMessage());
        }
    }
}