<?php
declare(strict_types=1);

/**
 * Capacidad de huéspedes por habitación.
 * Es el único lugar donde se define el límite: el portal del huésped (tarjetas, selector de personas y
 * ventana de reserva), el panel admin y controladores/guardar_reserva.php usan estos mismos valores.
 *
 *   personas = máximo de personas en la habitación (sin distinguir adultos y niños)
 */
function capacidad_habitacion(string $tipo): array
{
    $t = mb_strtolower(trim($tipo), 'UTF-8');

    if (str_contains($t, 'sencilla') || str_contains($t, 'simple') || str_contains($t, 'individual')) {
        return ['personas' => 3];
    }

    // Premium, suite, doble/matrimonial/familiar y cualquier otro tipo.
    return ['personas' => 5];
}

function texto_capacidad_habitacion(array $capacidad): string
{
    return 'máximo ' . $capacidad['personas'] . ' personas por habitación';
}

/**
 * Valida cuántas personas caben en las habitaciones elegidas.
 *
 * @param array<int, array{cod_hab:int, num_hab:int|string, tipo_hab:string}> $habitaciones
 * @param array<int, array{personas:int}>|null $distribucion  id de habitación => personas en ella
 *        (obligatoria cuando hay varias habitaciones desde el portal del huésped)
 * @return array<int, array{personas:int}> distribución validada (vacía si no se recibió una)
 * @throws RuntimeException con el mensaje 'ocupacion_invalida:<texto para el usuario>'
 */
function validar_ocupacion_habitaciones(array $habitaciones, int $personas, ?array $distribucion): array
{
    $falla = static function (string $mensaje): never {
        throw new RuntimeException('ocupacion_invalida:' . $mensaje);
    };
    $nombreHabitacion = static fn(array $h): string => 'La habitación ' . $h['num_hab'] . ' (' . $h['tipo_hab'] . ')';

    if ($distribucion !== null && $distribucion !== []) {
        $ids = array_map(static fn(array $h): int => (int) $h['cod_hab'], $habitaciones);
        $recibidas = array_keys($distribucion);
        sort($ids);
        sort($recibidas);
        if ($ids !== $recibidas) {
            $falla('Indica cuántas personas habrá en cada una de las habitaciones seleccionadas.');
        }

        $suma = 0;
        foreach ($habitaciones as $habitacion) {
            $enHabitacion = (int) $distribucion[(int) $habitacion['cod_hab']]['personas'];
            $capacidad = capacidad_habitacion((string) $habitacion['tipo_hab']);
            if ($enHabitacion < 1) {
                $falla($nombreHabitacion($habitacion) . ' necesita al menos una persona.');
            }
            if ($enHabitacion > $capacidad['personas']) {
                $falla($nombreHabitacion($habitacion) . ' admite ' . texto_capacidad_habitacion($capacidad) . '.');
            }
            $suma += $enHabitacion;
        }
        if ($suma !== $personas) {
            $falla('La distribución por habitación no coincide con el total de personas.');
        }

        return $distribucion;
    }

    // Sin distribución (una sola habitación o reservas hechas por el personal): se valida con la capacidad total.
    if (count($habitaciones) === 1) {
        $capacidad = capacidad_habitacion((string) $habitaciones[0]['tipo_hab']);
        if ($personas > $capacidad['personas']) {
            $falla($nombreHabitacion($habitaciones[0]) . ' admite ' . texto_capacidad_habitacion($capacidad) . '.');
        }
        return [];
    }

    $totalPersonas = 0;
    foreach ($habitaciones as $habitacion) {
        $totalPersonas += capacidad_habitacion((string) $habitacion['tipo_hab'])['personas'];
    }
    if ($personas > $totalPersonas) {
        $falla('Las habitaciones seleccionadas admiten hasta ' . $totalPersonas . ' personas en total.');
    }

    return [];
}