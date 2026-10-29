<?php
declare(strict_types=1);

/**
 * Capacidad de huéspedes por habitación, según su tipo.
 * Es el único lugar donde se definen los límites: el portal del huésped (tarjetas, selector de huéspedes y
 * ventana de reserva) y controladores/guardar_reserva.php usan estos mismos valores.
 *
 *   personas = máximo de personas (adultos + niños) en la habitación
 *   adultos  = máximo de adultos
 *   ninos    = máximo de niños
 * Cada habitación debe llevar al menos un adulto.
 */
function capacidad_habitacion(string $tipo): array
{
    $t = mb_strtolower(trim($tipo), 'UTF-8');

    if (str_contains($t, 'premium')) {
        return ['personas' => 6, 'adultos' => 4, 'ninos' => 3];
    }
    if (str_contains($t, 'suite')) {
        return ['personas' => 4, 'adultos' => 3, 'ninos' => 2];
    }
    if (str_contains($t, 'doble') || str_contains($t, 'matrimonial') || str_contains($t, 'familiar')) {
        return ['personas' => 4, 'adultos' => 4, 'ninos' => 2];
    }
    if (str_contains($t, 'sencilla') || str_contains($t, 'simple') || str_contains($t, 'individual')) {
        return ['personas' => 2, 'adultos' => 2, 'ninos' => 1];
    }

    return ['personas' => 4, 'adultos' => 4, 'ninos' => 2];
}

function texto_capacidad_habitacion(array $capacidad): string
{
    return 'hasta ' . $capacidad['personas'] . ' personas (máx. ' . $capacidad['adultos'] . ' adultos y '
        . $capacidad['ninos'] . ($capacidad['ninos'] === 1 ? ' niño)' : ' niños)');
}

/**
 * Valida cuántos huéspedes caben en las habitaciones elegidas.
 *
 * @param array<int, array{cod_hab:int, num_hab:int|string, tipo_hab:string}> $habitaciones
 * @param array<int, array{adultos:int, ninos:int}>|null $distribucion  id de habitación => huéspedes en ella
 *        (obligatoria en la práctica cuando hay varias habitaciones desde el portal del huésped)
 * @return array<int, array{adultos:int, ninos:int}> distribución validada (vacía si no se recibió una)
 * @throws RuntimeException con el mensaje 'ocupacion_invalida:<texto para el usuario>'
 */
function validar_ocupacion_habitaciones(array $habitaciones, int $adultos, int $ninos, ?array $distribucion): array
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

        $sumaAdultos = 0;
        $sumaNinos = 0;
        foreach ($habitaciones as $habitacion) {
            $huespedes = $distribucion[(int) $habitacion['cod_hab']];
            $capacidad = capacidad_habitacion((string) $habitacion['tipo_hab']);
            if ($huespedes['adultos'] < 1) {
                $falla($nombreHabitacion($habitacion) . ' necesita al menos un adulto.');
            }
            if ($huespedes['adultos'] > $capacidad['adultos']
                || $huespedes['ninos'] > $capacidad['ninos']
                || $huespedes['adultos'] + $huespedes['ninos'] > $capacidad['personas']) {
                $falla($nombreHabitacion($habitacion) . ' admite ' . texto_capacidad_habitacion($capacidad) . '.');
            }
            $sumaAdultos += $huespedes['adultos'];
            $sumaNinos += $huespedes['ninos'];
        }
        if ($sumaAdultos !== $adultos || $sumaNinos !== $ninos) {
            $falla('La distribución por habitación no coincide con el total de huéspedes.');
        }

        return $distribucion;
    }

    // Sin distribución (una sola habitación o reservas hechas por el personal): se valida con la capacidad total.
    $totalPersonas = 0;
    $totalAdultos = 0;
    $totalNinos = 0;
    foreach ($habitaciones as $habitacion) {
        $capacidad = capacidad_habitacion((string) $habitacion['tipo_hab']);
        $totalPersonas += $capacidad['personas'];
        $totalAdultos += $capacidad['adultos'];
        $totalNinos += $capacidad['ninos'];
    }
    if (count($habitaciones) === 1) {
        $capacidad = capacidad_habitacion((string) $habitaciones[0]['tipo_hab']);
        if ($adultos > $capacidad['adultos'] || $ninos > $capacidad['ninos'] || $adultos + $ninos > $capacidad['personas']) {
            $falla($nombreHabitacion($habitaciones[0]) . ' admite ' . texto_capacidad_habitacion($capacidad) . '.');
        }
        return [];
    }
    if ($adultos > $totalAdultos || $ninos > $totalNinos || $adultos + $ninos > $totalPersonas) {
        $falla('Las habitaciones seleccionadas admiten hasta ' . $totalPersonas . ' personas (máx. ' . $totalAdultos
            . ' adultos y ' . $totalNinos . ' niños en total).');
    }

    return [];
}