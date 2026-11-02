<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../configuracion/capacidad_habitaciones.php';
require_once __DIR__ . '/../src/Reserva/RoomRateCalculator.php';

header('Content-Type: application/json; charset=utf-8');

function responder_disponibilidad_admin(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    responder_disponibilidad_admin(405, ['status' => 'error', 'mensaje' => 'Método no permitido.']);
}

if (!isset($_SESSION['emp_auth']['id_usuario'])) {
    responder_disponibilidad_admin(401, ['status' => 'error', 'mensaje' => 'Inicia sesión con una cuenta autorizada.']);
}
if (!usuario_tiene_permiso($conexion, 'reservas.crear')) {
    responder_disponibilidad_admin(403, ['status' => 'error', 'mensaje' => 'No tienes permiso para crear reservas.']);
}

$checkin = trim((string) ($_GET['checkin'] ?? ''));
$checkout = trim((string) ($_GET['checkout'] ?? ''));
$adults = filter_var($_GET['adultos'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 20]]);
$children = filter_var($_GET['ninos'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 20]]);
$checkinDate = DateTimeImmutable::createFromFormat('!Y-m-d', $checkin);
$checkoutDate = DateTimeImmutable::createFromFormat('!Y-m-d', $checkout);

if (
    $adults === false || $children === false
    || $checkinDate === false || $checkoutDate === false
    || $checkinDate->format('Y-m-d') !== $checkin
    || $checkoutDate->format('Y-m-d') !== $checkout
    || $checkoutDate <= $checkinDate
) {
    responder_disponibilidad_admin(422, ['status' => 'error', 'mensaje' => 'Selecciona fechas y huéspedes válidos.']);
}

$today = (new DateTimeImmutable('today', new DateTimeZone('America/Bogota')))->format('Y-m-d');
if ($checkin < $today) {
    responder_disponibilidad_admin(422, ['status' => 'error', 'mensaje' => 'La fecha de entrada no puede estar en el pasado.']);
}

$checkinSql = $checkinDate->setTime(15, 0)->format('Y-m-d H:i:s');
$checkoutSql = $checkoutDate->setTime(12, 0)->format('Y-m-d H:i:s');
$statement = $conexion->prepare(
    "SELECT h.cod_hab, h.num_hab, h.tipo_hab, h.pre_hab, h.precio_hab
     FROM habitacion h
     WHERE LOWER(TRIM(COALESCE(h.est_hab, ''))) NOT IN ('mantenimiento', 'sucia')
       AND NOT EXISTS (
           SELECT 1
           FROM detalle d
           INNER JOIN reservas r ON r.cod_res = d.cod_res_det
           WHERE d.cod_hab_det = h.cod_hab
             AND LOWER(TRIM(COALESCE(r.est_res, ''))) NOT IN ('cancelada', 'cancelado', 'finalizada')
             AND ? < r.fec_sal_res
             AND ? > r.fec_ent_res
       )
     ORDER BY h.num_hab ASC"
);
if (!$statement) {
    error_log('No se pudo preparar la búsqueda de disponibilidad para reservas del panel.');
    responder_disponibilidad_admin(500, ['status' => 'error', 'mensaje' => 'No se pudo consultar la disponibilidad.']);
}

$statement->bind_param('ss', $checkinSql, $checkoutSql);
if (!$statement->execute()) {
    error_log('No se pudo ejecutar la búsqueda de disponibilidad para reservas del panel.');
    $statement->close();
    responder_disponibilidad_admin(500, ['status' => 'error', 'mensaje' => 'No se pudo consultar la disponibilidad.']);
}

$result = $statement->get_result();
$rooms = [];
while ($row = $result->fetch_assoc()) {
    $capacity = capacidad_habitacion((string) $row['tipo_hab']);
    if (
        $adults > $capacity['adultos']
        || $children > $capacity['ninos']
        || $adults + $children > $capacity['personas']
    ) {
        continue;
    }

    $configuredPrice = (float) ($row['pre_hab'] ?: $row['precio_hab']);
    $rooms[] = [
        'id' => (int) $row['cod_hab'],
        'numero' => (int) $row['num_hab'],
        'tipo' => (string) $row['tipo_hab'],
        'precio' => \App\Reserva\RoomRateCalculator::standardNightlyRate((string) $row['tipo_hab'], $configuredPrice),
        'precio_una_persona' => preg_match('/sencilla|simple|individual/i', (string) $row['tipo_hab']) === 1
            && $configuredPrice === 50000.0 ? 50000.0 : null,
        'capacidad' => $capacity,
    ];
}
$statement->close();

responder_disponibilidad_admin(200, [
    'status' => 'exito',
    'mensaje' => count($rooms) > 0
        ? 'Habitaciones disponibles para las fechas y huéspedes seleccionados.'
        : 'No hay habitaciones disponibles para ese rango y ocupación.',
    'habitaciones' => $rooms,
]);
