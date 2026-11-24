<?php
/**
 * API REST Móvil — Crear Reserva
 * Hotel Aurora Sanctuary & Concierge
 * Ejecuta transacción en `reservas`, `detalle`, `pagos` y `factura`
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
        'message' => 'Cuerpo JSON de la petición no válido.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$id_usu_res = (int) ($data['id_usu_res'] ?? 0);
$fec_ent_res = trim((string) ($data['fec_ent_res'] ?? ''));
$fec_sal_res = trim((string) ($data['fec_sal_res'] ?? ''));
$adultos = max(1, (int) ($data['adultos'] ?? 1));
$ninos = max(0, (int) ($data['ninos'] ?? 0));
$metodo_pago = trim((string) ($data['metodo_pago'] ?? 'Recepción'));
$habitaciones = $data['habitaciones'] ?? [];
$notasTexto = trim((string) ($data['notas'] ?? $data['peticion_especial'] ?? ''));

if ($id_usu_res <= 0 || $fec_ent_res === '' || $fec_sal_res === '' || empty($habitaciones)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Faltan parámetros obligatorios (huésped, fechas o habitaciones).'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Calcular número de noches
$d1 = new DateTime($fec_ent_res);
$d2 = new DateTime($fec_sal_res);
$diff = $d1->diff($d2);
$noches = max(1, (int) $diff->days);

// Consultar tarifa de suplemento por niño en tarifa_hotel_configuracion
$suplementoNinoTarifa = 40000;
$resTarifa = $conexion->query("SELECT valor FROM tarifa_hotel_configuracion WHERE clave = 'suplemento_nino_adicional' LIMIT 1");
if ($resTarifa && $rowT = $resTarifa->fetch_assoc()) {
    $suplementoNinoTarifa = (float) $rowT['valor'];
}

// Calcular totales
$subtotalHabitaciones = 0;
foreach ($habitaciones as $h) {
    $precioNoche = (float) ($h['precio_noche'] ?? 90000);
    $subtotalHabitaciones += ($precioNoche * $noches);
}

$suplementoNinosTotal = $ninos * $suplementoNinoTarifa * $noches;
$subtotalNeto = $subtotalHabitaciones + $suplementoNinosTotal;
$iva = round($subtotalNeto * 0.19);
$totalGeneral = $subtotalNeto + $iva;

$notasJson = json_encode([
    'adultos' => $adultos,
    'ninos' => $ninos,
    'metodo_pago' => $metodo_pago,
    'peticion_especial' => $notasTexto,
    'observaciones' => $notasTexto,
    'noches' => $noches,
    'subtotalHabitaciones' => $subtotalHabitaciones,
    'suplementoNinos' => $suplementoNinosTotal,
    'subtotalNeto' => $subtotalNeto,
    'iva' => $iva,
    'total' => $totalGeneral
], JSON_UNESCAPED_UNICODE);

// Iniciar transacción
$conexion->begin_transaction();

try {
    // 1. Insertar en `reservas`
    $fecEntDateTime = $fec_ent_res . ' 15:00:00';
    $fecSalDateTime = $fec_sal_res . ' 12:00:00';
    $estadoInicial = 'Pendiente';

    $stmtRes = $conexion->prepare("INSERT INTO reservas (fec_res, fec_ent_res, fec_sal_res, est_res, not_res, id_usu_res) VALUES (NOW(), ?, ?, ?, ?, ?)");
    $stmtRes->bind_param('ssssi', $fecEntDateTime, $fecSalDateTime, $estadoInicial, $notasJson, $id_usu_res);
    $stmtRes->execute();
    $cod_res = $conexion->insert_id;
    $stmtRes->close();

    // 2. Insertar en `detalle` por cada habitación
    $stmtDet = $conexion->prepare("INSERT INTO detalle (can_noc_det, cod_res_det, cod_hab_det, precio_noche_det) VALUES (?, ?, ?, ?)");
    foreach ($habitaciones as $hab) {
        $codHab = (int) $hab['cod_hab'];
        $precioNoche = (float) ($hab['precio_noche'] ?? 90000);
        $stmtDet->bind_param('iiid', $noches, $cod_res, $codHab, $precioNoche);
        $stmtDet->execute();
    }
    $stmtDet->close();

    // 3. Insertar en `pagos`
    $referencia = 'REF-MOB-' . time() . '-' . $cod_res;
    $estadoPago = ($metodo_pago === 'Recepción' || $metodo_pago === 'En Recepción') ? 'Pendiente' : 'Aprobado';
    $stmtPago = $conexion->prepare("INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago, fecha_pago, concepto) VALUES (?, ?, ?, ?, ?, NOW(), 'alojamiento')");
    $stmtPago->bind_param('idsss', $cod_res, $totalGeneral, $metodo_pago, $referencia, $estadoPago);
    $stmtPago->execute();
    $stmtPago->close();

    // 4. Insertar folio en `factura`
    $folio = 'FAC-' . $cod_res;
    $stmtFac = $conexion->prepare("INSERT INTO factura (fech_fac, tot_fac, est_fac, cod_res_fac, folio, tipo_fac, metodo_pago_fac) VALUES (NOW(), ?, 'Pendiente', ?, ?, 'alojamiento', ?)");
    $stmtFac->bind_param('diss', $totalGeneral, $cod_res, $folio, $metodo_pago);
    $stmtFac->execute();
    $stmtFac->close();

    // Confirmar transacción
    $conexion->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Reserva creada exitosamente en MariaDB.',
        'cod_res' => $cod_res,
        'folio' => $folio,
        'total' => $totalGeneral
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $conexion->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar reserva: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
