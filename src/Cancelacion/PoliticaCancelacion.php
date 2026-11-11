<?php
declare(strict_types=1);

namespace App\Cancelacion;

final class PoliticaCancelacion
{
    public const VENTANA_MINUTOS = 60;

    public static function etiquetaVentana(): string
    {
        if (self::VENTANA_MINUTOS % 60 === 0) {
            $horas = intdiv(self::VENTANA_MINUTOS, 60);
            return $horas . ($horas === 1 ? ' hora' : ' horas');
        }

        return self::VENTANA_MINUTOS . ' minutos';
    }
}
