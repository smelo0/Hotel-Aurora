<?php
declare(strict_types=1);

namespace App\Reembolso;

use App\Cancelacion\PoliticaCancelacion;
use DateTimeImmutable;

/**
 * Política de reembolso del alojamiento.
 *
 *  - Dentro de la ventana de arrepentimiento (PoliticaCancelacion::VENTANA_MINUTOS desde que se
 *    hizo la reserva): 100 %.
 *  - 7 días o más antes del check-in: 100 %.
 *  - Entre 3 y 6 días antes del check-in: 50 %.
 *  - Menos de 3 días: sin reembolso.
 *
 * Solo aplica a pagos de alojamiento. Experiencias y room service ya consumidos no se reembolsan.
 * Para cambiar la política basta con editar los valores de TRAMOS.
 */
final class PoliticaReembolso
{
    /** [días mínimos de antelación al check-in, porcentaje a reembolsar] de mayor a menor. */
    public const TRAMOS = [
        [7, 100],
        [3, 50],
    ];

    public static function porcentaje(DateTimeImmutable $ahora, DateTimeImmutable $fechaReserva, DateTimeImmutable $checkIn): int
    {
        if ($checkIn <= $ahora) {
            return 0;
        }
        $minutosDesdeReserva = ($ahora->getTimestamp() - $fechaReserva->getTimestamp()) / 60;
        if ($minutosDesdeReserva <= PoliticaCancelacion::VENTANA_MINUTOS) {
            return 100;
        }
        $diasAntelacion = ($checkIn->getTimestamp() - $ahora->getTimestamp()) / 86400;
        foreach (self::TRAMOS as [$dias, $porcentaje]) {
            if ($diasAntelacion >= $dias) {
                return $porcentaje;
            }
        }
        return 0;
    }

    public static function descripcion(): string
    {
        $partes = [];
        foreach (self::TRAMOS as [$dias, $porcentaje]) {
            $partes[] = $porcentaje . '% si faltan ' . $dias . ' días o más para el check-in';
        }
        $partes[] = 'sin reembolso con menos de ' . self::TRAMOS[array_key_last(self::TRAMOS)][0] . ' días';
        return implode(' · ', $partes);
    }
}