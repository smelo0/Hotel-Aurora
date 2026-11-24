<?php
/**
 * API REST Móvil — Actualizar / Cancelar Reserva
 * Hotel Aurora Sanctuary & Concierge
 * Sincronización con tabla `reservas`, `habitacion` y `pagos` en MariaDB
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../configuracion/conexion.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Cuerpo JSON no válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$cod_res = (int) ($data['cod_res'] ?? 0);
$cod_rol_usu = (int) ($data['cod_rol_usu'] ?? 0);
$id_usu = (int) ($data['id_usu'] ?? 0);
$accion = trim((string) ($data['accion'] ?? ''));
$nuevo_estado = trim((string) ($data['nuevo_estado'] ?? ''));

if ($cod_res <= 0 || $accion === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Código de reserva o acción no especificada.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Consultar reserva actual
$stmtCheck = $conexion->prepare("SELECT cod_res, est_res, id_usu_res FROM reservas WHERE cod_res = ? LIMIT 1");
$stmtCheck->bind_param('i', $cod_res);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows !== 1) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Reserva no encontrada.'
    ], JSON_UNESCAPED_UNICODE);
    $stmtCheck->close();
    exit();
}

$reserva = $resCheck->fetch_assoc();
$stmtCheck->close();

// Validación de permisos para Cliente (Rol 6)
if ($cod_rol_usu === 6) {
    if ((int) $reserva['id_usu_res'] !== $id_usu) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'No tiene permisos para modificar esta reserva.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if ($accion === 'cancelar') {
        $estadoActual = $reserva['est_res'];
        if ($estadoActual !== 'Pendiente' && $estadoActual !== 'Confirmada') {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Solo se pueden cancelar reservas en estado Pendiente o Confirmada.'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
    } else {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Los clientes únicamente pueden cancelar reservas propias.'
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

// Ejecución de acciones
if ($accion === 'cancelar') {
    $stmtUp = $conexion->prepare("UPDATE reservas SET est_res = 'Cancelada' WHERE cod_res = ?");
    $stmtUp->bind_param('i', $cod_res);
    $stmtUp->execute();
    $stmtUp->close();

    // Liberar habitaciones asociadas
    $conexion->query("UPDATE habitacion SET est_hab = 'Disponible' WHERE cod_hab IN (SELECT cod_hab_det FROM detalle WHERE cod_res_det = {$cod_res})");

    echo json_encode([
        'success' => true,
        'message' => 'Reserva cancelada correctamente en MariaDB.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($accion === 'checkin') {
    $stmtUp = $conexion->prepare("UPDATE reservas SET est_res = 'En Casa' WHERE cod_res = ?");
    $stmtUp->bind_param('i', $cod_res);
    $stmtUp->execute();
    $stmtUp->close();

    // Marcar habitaciones como ocupadas
    $conexion->query("UPDATE habitacion SET est_hab = 'Ocupada' WHERE cod_hab IN (SELECT cod_hab_det FROM detalle WHERE cod_res_det = {$cod_res})");

    echo json_encode([
        'success' => true,
        'message' => "Check-in realizado con éxito. Habitación marcada como 'Ocupada'."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($accion === 'checkout') {
    // Liberar habitación para aseo/disponible
    $conexion->query("UPDATE habitacion SET est_hab = 'Disponible' WHERE cod_hab IN (SELECT cod_hab_det FROM detalle WHERE cod_res_det = {$cod_res})");

    echo json_encode([
        'success' => true,
        'message' => "Check-out procesado. Habitación liberada y marcada como 'Disponible'."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($accion === 'reactivar') {
    $stmtUp = $conexion->prepare("UPDATE reservas SET est_res = 'Pendiente' WHERE cod_res = ?");
    $stmtUp->bind_param('i', $cod_res);
    $stmtUp->execute();
    $stmtUp->close();

    echo json_encode([
        'success' => true,
        'message' => 'Folio reactivado como Pendiente.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($accion === 'cambiar_estado' && $nuevo_estado !== '') {
    $stmtUp = $conexion->prepare("UPDATE reservas SET est_res = ? WHERE cod_res = ?");
    $stmtUp->bind_param('si', $nuevo_estado, $cod_res);
    $stmtUp->execute();
    $stmtUp->close();

    echo json_encode([
        'success' => true,
        'message' => "Estado actualizado a '{$nuevo_estado}'."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($accion === 'editar') {
    $fecEnt = trim((string) ($data['fec_ent_res'] ?? ''));
    $fecSal = trim((string) ($data['fec_sal_res'] ?? ''));
    $notRes = trim((string) ($data['not_res'] ?? ''));
    $estRes = $nuevo_estado !== '' ? $nuevo_estado : $reserva['est_res'];

    $setParts = [];
    $params = [];
    $types = '';

    if ($estRes !== '') {
        $setParts[] = 'est_res = ?';
        $params[] = $estRes;
        $types .= 's';
    }
    if ($fecEnt !== '') {
        $setParts[] = 'fec_ent_res = ?';
        $params[] = $fecEnt . ' 15:00:00';
        $types .= 's';
    }
    if ($fecSal !== '') {
        $setParts[] = 'fec_sal_res = ?';
        $params[] = $fecSal . ' 12:00:00';
        $types .= 's';
    }
    if ($notRes !== '') {
        $setParts[] = 'not_res = ?';
        $params[] = $notRes;
        $types .= 's';
    }

    if (!empty($setParts)) {
        $sql = "UPDATE reservas SET " . implode(', ', $setParts) . " WHERE cod_res = ?";
        $params[] = $cod_res;
        $types .= 'i';

        $stmtUp = $conexion->prepare($sql);
        $stmtUp->bind_param($types, ...$params);
        $stmtUp->execute();
        $stmtUp->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Reserva editada y guardada en MariaDB.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode([
    'success' => true,
    'message' => 'Acción completada.'
], JSON_UNESCAPED_UNICODE);
