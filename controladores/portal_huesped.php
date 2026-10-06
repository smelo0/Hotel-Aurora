<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../src/Usuario/PortalRepository.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
header('Content-Type: application/json; charset=utf-8');
$portalRepository = new \App\Usuario\PortalRepository();

if (!function_exists('jsonResponse')) {
    function jsonResponse(int $statusCode, array $payload): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit();
    }
}
$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($httpMethod === 'POST' && ($_POST['accion'] ?? '') === 'agendar_actividad') {
    header('Content-Type: application/json; charset=utf-8');

    $idUsuario = (int) ($_SESSION['user_auth']['id_usuario'] ?? 0);
    $rolUsuario = (int) ($_SESSION['user_auth']['rol_usuario'] ?? 0);
    if ($idUsuario < 1 || $rolUsuario !== 6) {
        jsonResponse(401, ['status' => 'error', 'mensaje' => 'Inicia sesión con una cuenta de huésped para programar una experiencia.']);
    }
    exigir_csrf();
    $experienciaIdRaw = $_POST['experiencia_id'] ?? null;
    $participantesRaw = $_POST['participantes'] ?? null;
    $cantidadPersonasRaw = $_POST['cantidad_personas'] ?? null;
    $fechaRaw = $_POST['fecha'] ?? null;
    $horaRaw = $_POST['hora'] ?? null;
    $nombreRaw = $_POST['nombre'] ?? ($_SESSION['user_auth']['nombre_usuario'] ?? '');
    $correoRaw = $_POST['correo'] ?? '';
    $experienciaPersonalizadaRaw = $_POST['experiencia_personalizada'] ?? null;
    $categoriaPersonalizadaRaw = $_POST['categoria_personalizada'] ?? null;
    $solicitudPersonalizadaRaw = $_POST['solicitud_personalizada'] ?? '0';

    if (($experienciaIdRaw !== null && !is_string($experienciaIdRaw))
        || !is_string($participantesRaw)
        || !is_string($cantidadPersonasRaw)
        || ($fechaRaw !== null && !is_string($fechaRaw))
        || ($horaRaw !== null && !is_string($horaRaw))
        || !is_string($nombreRaw) || !is_string($correoRaw)
        || ($experienciaPersonalizadaRaw !== null && !is_string($experienciaPersonalizadaRaw))
        || ($categoriaPersonalizadaRaw !== null && !is_string($categoriaPersonalizadaRaw))
        || !is_string($solicitudPersonalizadaRaw)) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Completa correctamente todos los campos de la experiencia.']);
    }

    $experienciaPersonalizada = trim((string) $experienciaPersonalizadaRaw);
    $categoriaPersonalizada = trim((string) $categoriaPersonalizadaRaw);
    $solicitudPersonalizada = in_array(strtolower(trim((string) $solicitudPersonalizadaRaw)), ['1', 'true', 'on'], true);
    $esPersonalizada = $solicitudPersonalizada || $categoriaPersonalizada !== '' || $experienciaPersonalizada !== '';
    $experienciaId = $esPersonalizada ? null : filter_var((string) $experienciaIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $cantidadPersonas = filter_var($cantidadPersonasRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
    $participantes = json_decode($participantesRaw, true);
    $fecha = trim((string) ($fechaRaw ?? ''));
    $hora = trim((string) ($horaRaw ?? ''));
    $nombre = trim($nombreRaw);
    $correo = trim($correoRaw);

    if (!$cantidadPersonas || !is_array($participantes) || !array_is_list($participantes)
        || $nombre === '' || mb_strlen($nombre) > 140 || mb_strlen($correo) > 140 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Completa correctamente todos los campos de la experiencia.']);
    }

    if ($esPersonalizada) {
        if (count($participantes) > 0 && count($participantes) !== $cantidadPersonas) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'La cantidad de personas no coincide con la reserva.']);
        }
        $participantes = array_fill(0, $cantidadPersonas, 'Solicitud personalizada');
    } else {
        if (!$experienciaId || count($participantes) !== $cantidadPersonas) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'Completa correctamente todos los campos de la experiencia.']);
        }
        $fechaValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($fechaValidada === false || $fechaValidada->format('Y-m-d') !== $fecha || $fecha < date('Y-m-d')) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'Completa correctamente todos los campos de la experiencia.']);
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora)) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'Completa correctamente todos los campos de la experiencia.']);
        }
    }

    try {
        $idReservaExperiencia = $portalRepository->findReservationForExperience(
            $conexion,
            $idUsuario,
            $esPersonalizada ? null : $fecha
        );
    } catch (Throwable $error) {
        error_log('Error al validar la estancia para programar una experiencia: ' . $error->getMessage());
        jsonResponse(500, ['status' => 'error', 'mensaje' => 'No fue posible validar la reserva para la experiencia.']);
    }
    if (!$esPersonalizada && $idReservaExperiencia === null) {
        jsonResponse(403, ['status' => 'error', 'mensaje' => 'La fecha de la experiencia debe estar dentro de una estancia reservada a tu nombre.']);
    }

    try {
        if ($esPersonalizada) {
            $actividadPersonalizada = $experienciaPersonalizada !== '' ? $experienciaPersonalizada : ($categoriaPersonalizada !== '' ? $categoriaPersonalizada : 'Solicitud personalizada');
            $seleccionesJson = json_encode(array_fill(0, $cantidadPersonas, 'Solicitud personalizada'), JSON_UNESCAPED_UNICODE);
            $preciosJson = json_encode(array_fill(0, $cantidadPersonas, null), JSON_UNESCAPED_UNICODE);
            if ($seleccionesJson === false || $preciosJson === false) {
                throw new RuntimeException('No se pudieron procesar las opciones de la experiencia personalizada.');
            }
            $portalRepository->insertCustomActivity(
                $conexion,
                $actividadPersonalizada,
                $seleccionesJson,
                $nombre,
                $correo,
                $idUsuario,
                $preciosJson,
                $idReservaExperiencia
            );
            jsonResponse(200, ['status' => 'exito', 'mensaje' => 'Tu solicitud personalizada fue enviada correctamente.']);
        }

        $experiencia = $portalRepository->fetchExperienceForActivity($conexion, (int) $experienciaId);
        if (!$experiencia) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'La experiencia seleccionada ya no está disponible.']);
        }

        $opcionesExperiencia = [
            1 => trim((string) $experiencia['opcion_1']),
            2 => trim((string) $experiencia['opcion_2']),
            3 => trim((string) $experiencia['opcion_3']),
        ];
        $preciosExperiencia = [
            $experiencia['precio_opcion_1'] === null ? null : (string) $experiencia['precio_opcion_1'],
            $experiencia['precio_opcion_2'] === null ? null : (string) $experiencia['precio_opcion_2'],
            $experiencia['precio_opcion_3'] === null ? null : (string) $experiencia['precio_opcion_3'],
        ];
        $seleccionesValidadas = [];
        $indicesOpcionesSeleccionadas = [];
        $preciosPorPersona = [];
        $totalExperiencia = 0;
        $totalConocido = true;
        foreach ($participantes as $seleccionPersona) {
            if (!is_string($seleccionPersona) || !in_array($seleccionPersona, array_filter($opcionesExperiencia, static fn(string $opcion): bool => $opcion !== ''), true)) {
                jsonResponse(422, ['status' => 'error', 'mensaje' => 'Elige una opción válida para cada persona.']);
            }
            $seleccionesValidadas[] = $seleccionPersona;
            $indiceOpcion = array_search($seleccionPersona, $opcionesExperiencia, true);
            $indicesOpcionesSeleccionadas[] = (string) $indiceOpcion;
            $precioPersona = $preciosExperiencia[$indiceOpcion - 1];
            $preciosPorPersona[] = $precioPersona;
            if ($precioPersona === null) {
                $totalConocido = false;
            } else {
                $totalExperiencia += (int) $precioPersona;
            }
        }
        $seleccionesJson = json_encode($seleccionesValidadas, JSON_UNESCAPED_UNICODE);
        if ($seleccionesJson === false) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'No se pudieron procesar las opciones de cada persona.']);
        }
        $preciosJson = json_encode($preciosPorPersona, JSON_UNESCAPED_UNICODE);
        if ($preciosJson === false) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'No se pudieron procesar los precios de las opciones.']);
        }
        $montoExperiencia = $totalConocido ? number_format($totalExperiencia, 2, '.', '') : null;
        $opcionResumen = count($seleccionesValidadas) . ' personas';

        $horariosDecodificados = json_decode((string) ($experiencia['horarios_json'] ?? ''), true);
        $horariosExperiencia = is_array($horariosDecodificados)
            ? \App\Experiencia\ExperienceSchedule::normalizar($horariosDecodificados, array_values($opcionesExperiencia))
            : [];
        $minutosSeleccionados = ((int) substr($hora, 0, 2) * 60 + (int) substr($hora, 3, 2));
        $horarioDisponible = true;
        foreach (array_unique($indicesOpcionesSeleccionadas) as $indiceOpcion) {
            $rangoHorario = $horariosExperiencia[$indiceOpcion][$fecha] ?? null;
            $minutosInicio = is_array($rangoHorario) ? ((int) substr($rangoHorario['inicio'], 0, 2) * 60 + (int) substr($rangoHorario['inicio'], 3, 2)) : -1;
            $minutosFin = is_array($rangoHorario) ? ((int) substr($rangoHorario['fin'], 0, 2) * 60 + (int) substr($rangoHorario['fin'], 3, 2)) : -1;
            if (!is_array($rangoHorario) || $minutosSeleccionados < $minutosInicio || $minutosSeleccionados > $minutosFin
                || ($minutosSeleccionados - $minutosInicio) % 30 !== 0) {
                $horarioDisponible = false;
                break;
            }
        }
        if (!$horarioDisponible || ($fecha === date('Y-m-d') && $hora <= date('H:i'))) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'El horario no está disponible para ese día. Elige uno de los horarios publicados.']);
        }

        $actividad = (string) $experiencia['nombre'];
        $portalRepository->insertScheduledActivity(
            $conexion,
            $actividad,
            $opcionResumen,
            $seleccionesJson,
            $fecha,
            $hora,
            $nombre,
            $correo,
            $idUsuario,
            $montoExperiencia,
            $preciosJson,
            $idReservaExperiencia
        );

        jsonResponse(200, ['status' => 'exito', 'mensaje' => 'Experiencia programada correctamente.']);
    } catch (Throwable $error) {
        error_log('Error al registrar solicitud de experiencia: ' . $error->getMessage());
        jsonResponse(500, ['status' => 'error', 'mensaje' => 'No fue posible guardar la experiencia.']);
    }
}

if ($httpMethod === 'POST' && ($_POST['accion'] ?? '') === 'buscar_disponibilidad') {
    header('Content-Type: application/json; charset=utf-8');

    $checkinRaw = $_POST['checkin'] ?? '';
    $checkoutRaw = $_POST['checkout'] ?? '';
    $adultosRaw = $_POST['adultos'] ?? '2';
    $ninosRaw = $_POST['ninos'] ?? '0';
    if (!is_string($checkinRaw) || !is_string($checkoutRaw)
        || !is_string($adultosRaw) || !is_string($ninosRaw)) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona fechas y huéspedes válidos.']);
    }

    $checkin = trim($checkinRaw);
    $checkout = trim($checkoutRaw);
    $adultos = filter_var($adultosRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
    $ninos = filter_var($ninosRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 20]]);
    if ($adultos === false || $ninos === false) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'La cantidad de huéspedes no es válida.']);
    }

    $checkinDate = DateTimeImmutable::createFromFormat('!Y-m-d', $checkin);
    $checkinErrors = DateTimeImmutable::getLastErrors();
    $checkoutDate = DateTimeImmutable::createFromFormat('!Y-m-d', $checkout);
    $checkoutErrors = DateTimeImmutable::getLastErrors();

    if ($checkinDate === false || $checkoutDate === false
        || ($checkinErrors !== false && ($checkinErrors['warning_count'] > 0 || $checkinErrors['error_count'] > 0))
        || ($checkoutErrors !== false && ($checkoutErrors['warning_count'] > 0 || $checkoutErrors['error_count'] > 0))
        || $checkinDate->format('Y-m-d') !== $checkin
        || $checkoutDate->format('Y-m-d') !== $checkout) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona fechas válidas.']);
    }

    if ($checkoutDate <= $checkinDate) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'El check-out debe ser posterior al check-in.']);
    }

    $checkinSql = $checkinDate->setTime(15, 0, 0)->format('Y-m-d H:i:s');
    $checkoutSql = $checkoutDate->setTime(12, 0, 0)->format('Y-m-d H:i:s');
    $availableIds = [];

    try {
        $availableIds = $portalRepository->fetchAvailableRoomIds($conexion, $checkinSql, $checkoutSql);
    } catch (Throwable $error) {
        error_log('Error al consultar disponibilidad de habitaciones: ' . $error->getMessage());
        jsonResponse(500, ['status' => 'error', 'mensaje' => 'No fue posible consultar la disponibilidad.']);
    }

    jsonResponse(200, [
        'status' => 'exito',
        'mensaje' => 'Disponibilidad actualizada en tiempo real.',
        'total_disponibles' => count($availableIds),
        'habitaciones_ids' => $availableIds,
        'filtros' => [
            'checkin' => $checkin,
            'checkout' => $checkout,
            'adultos' => $adultos,
            'ninos' => $ninos,
        ],
    ]);
}


jsonResponse(405, ['status' => 'error', 'mensaje' => 'Acción no permitida.']);