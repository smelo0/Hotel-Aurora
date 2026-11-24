<?php
/**
 * API REST Móvil — Listado de Reservas
 * Hotel Aurora Sanctuary & Concierge
 * Consulta en tiempo real a MariaDB
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../configuracion/conexion.php';

$id_usu = isset($_GET['id_usu']) ? (int) $_GET['id_usu'] : 0;
$cod_rol = isset($_GET['cod_rol']) ? (int) $_GET['cod_rol'] : 0;
$estado = isset($_GET['estado']) ? trim((string) $_GET['estado']) : '';

$whereClauses = [];
$params = [];
$types = '';

if ($cod_rol === 6) {
    $whereClauses[] = 'v.id_usu_res = ?';
    $params[] = $id_usu;
    $types .= 'i';
}

if ($estado !== '' && strtolower($estado) !== 'todas') {
    $whereClauses[] = 'v.est_res = ?';
    $params[] = $estado;
    $types .= 's';
}

$whereSql = '';
if (count($whereClauses) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Consulta agrupada por reserva única
$sql = "SELECT 
            v.cod_res,
            DATE_FORMAT(v.fec_res, '%Y-%m-%d %H:%i:%s') AS fec_res,
            DATE_FORMAT(v.fec_ent_res, '%Y-%m-%d') AS fec_ent_res,
            DATE_FORMAT(v.fec_sal_res, '%Y-%m-%d') AS fec_sal_res,
            DATE_FORMAT(v.fec_ent_res, '%H:%i hrs') AS hora_ent,
            DATE_FORMAT(v.fec_sal_res, '%H:%i hrs') AS hora_sal,
            v.est_res,
            v.id_usu_res,
            v.huesped,
            v.correo,
            v.cantidad_habitaciones,
            v.numeros_habitaciones,
            v.tipos_habitacion,
            r.not_res,
            COALESCE((SELECT f.folio FROM factura f WHERE f.cod_res_fac = v.cod_res AND f.tipo_fac = 'alojamiento' LIMIT 1), CONCAT('FAC-', v.cod_res)) AS folio,
            COALESCE((SELECT p.monto FROM pagos p WHERE p.cod_res_pago = v.cod_res AND p.concepto = 'alojamiento' LIMIT 1), (SELECT f.tot_fac FROM factura f WHERE f.cod_res_fac = v.cod_res LIMIT 1), 0) AS total,
            COALESCE((SELECT p.metodo_pago FROM pagos p WHERE p.cod_res_pago = v.cod_res AND p.concepto = 'alojamiento' LIMIT 1), 'Recepción') AS metodo_pago,
            COALESCE((SELECT p.estado_pago FROM pagos p WHERE p.cod_res_pago = v.cod_res AND p.concepto = 'alojamiento' LIMIT 1), 'Pendiente') AS estado_pago,
            u.doc_usu AS documento
        FROM vista_reservas_habitaciones v
        INNER JOIN reservas r ON r.cod_res = v.cod_res
        LEFT JOIN usuario u ON u.id_usu = v.id_usu_res
        {$whereSql}
        ORDER BY v.cod_res DESC";

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar consulta: ' . $conexion->error
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();

$reservas = [];
while ($row = $resultado->fetch_assoc()) {
    $notasRaw = $row['not_res'];
    $parsedNotas = [];

    if ($notasRaw !== null && $notasRaw !== '') {
        $decoded = json_decode($notasRaw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $parsedNotas = $decoded;
        } else {
            $parsedNotas = ['observaciones' => $notasRaw];
        }
    }

    $reservas[] = [
        'cod_res' => (int) $row['cod_res'],
        'folio' => (string) $row['folio'],
        'fec_res' => (string) $row['fec_res'],
        'fec_ent_res' => (string) $row['fec_ent_res'],
        'fec_sal_res' => (string) $row['fec_sal_res'],
        'hora_ent' => $row['hora_ent'] ?: '15:00 hrs',
        'hora_sal' => $row['hora_sal'] ?: '12:00 hrs',
        'est_res' => (string) $row['est_res'],
        'id_usu_res' => (int) $row['id_usu_res'],
        'huesped' => (string) ($row['huesped'] ?: 'Huésped'),
        'documento' => $row['documento'] ? 'CC ' . $row['documento'] : null,
        'correo' => (string) $row['correo'],
        'cantidad_habitaciones' => (int) $row['cantidad_habitaciones'],
        'numeros_habitaciones' => $row['numeros_habitaciones'] ? 'Hab. ' . $row['numeros_habitaciones'] : 'Habitación',
        'tipos_habitacion' => (string) $row['tipos_habitacion'],
        'total' => (float) $row['total'],
        'metodo_pago' => (string) $row['metodo_pago'],
        'estado_pago' => (string) $row['estado_pago'],
        'not_res' => $parsedNotas,
        'peticion_especial' => $parsedNotas['peticion_especial'] ?? null,
    ];
}

$stmt->close();

echo json_encode($reservas, JSON_UNESCAPED_UNICODE);
