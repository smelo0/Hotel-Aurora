<?php
declare(strict_types=1);

/**
 * Historial de facturas del personal.
 *   GET  accion=listar [q]        -> últimas 5 (recepción) o todas (con acceso completo)
 *   GET  accion=ver id            -> factura lista para ver/imprimir
 *   POST accion=solicitar_acceso  -> pide acceso completo al administrador
 *   POST accion=resolver_acceso id_acceso decision=aprobar|rechazar
 */

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Billing\FacturaHistorial;

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function facturas_responder(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

/** @var mysqli $conexion */
$idEmpleado = (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0);
if ($idEmpleado < 1) {
    facturas_responder(401, ['status' => 'error', 'mensaje' => 'Inicia sesión como personal del hotel.']);
}
exigir_permiso($conexion, 'facturas.ver');

$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = (string) ($_POST['accion'] ?? $_GET['accion'] ?? '');

try {
    $repo = new FacturaHistorial($conexion);
    $accesoTemporal = $repo->accesoTemporalVigente($idEmpleado);
    $accesoTotal = usuario_tiene_permiso($conexion, 'facturas.ver_todas') || $accesoTemporal !== null;
    $puedeAprobar = usuario_tiene_permiso($conexion, 'facturas.aprobar_acceso');

    if ($accion === 'listar' && $metodo === 'GET') {
        $solicitud = $repo->ultimaSolicitud($idEmpleado);
        facturas_responder(200, [
            'status' => 'exito',
            'facturas' => $repo->listar($accesoTotal, (string) ($_GET['q'] ?? '')),
            'acceso_total' => $accesoTotal,
            'acceso_temporal' => $accesoTemporal !== null && !usuario_tiene_permiso($conexion, 'facturas.ver_todas'),
            'vence_en' => $accesoTemporal['vence_en'] ?? null,
            'limite' => $accesoTotal ? null : FacturaHistorial::LIMITE_RECEPCION,
            'minutos_acceso' => FacturaHistorial::MINUTOS_ACCESO,
            'solicitud_estado' => $solicitud['estado'] ?? null,
            'puede_aprobar' => $puedeAprobar,
            'pendientes' => $puedeAprobar ? $repo->solicitudesPendientes() : [],
        ]);
    }

    if ($accion === 'ver' && $metodo === 'GET') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            facturas_responder(422, ['status' => 'error', 'mensaje' => 'La factura no es válida.']);
        }
        facturas_responder(200, ['status' => 'exito', 'factura' => $repo->obtener($id, $accesoTotal)]);
    }

    if ($accion === 'solicitar_acceso' && $metodo === 'POST') {
        exigir_csrf();
        if ($accesoTotal) {
            facturas_responder(200, ['status' => 'exito', 'mensaje' => 'Ya tienes acceso al historial completo.']);
        }
        $repo->solicitarAcceso($idEmpleado);
        facturas_responder(200, ['status' => 'exito', 'mensaje' => 'Solicitud enviada. El administrador debe aprobarla.']);
    }

    if ($accion === 'resolver_acceso' && $metodo === 'POST') {
        exigir_permiso($conexion, 'facturas.aprobar_acceso');
        exigir_csrf();
        $idAcceso = filter_var($_POST['id_acceso'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $aprobar = ($_POST['decision'] ?? '') === 'aprobar';
        if (!$idAcceso || !in_array($_POST['decision'] ?? '', ['aprobar', 'rechazar'], true)) {
            facturas_responder(422, ['status' => 'error', 'mensaje' => 'Solicitud no válida.']);
        }
        $repo->resolverAcceso($idAcceso, $aprobar, $idEmpleado);
        facturas_responder(200, [
            'status' => 'exito',
            'mensaje' => $aprobar
                ? 'Acceso aprobado por ' . FacturaHistorial::MINUTOS_ACCESO . ' minutos.'
                : 'Solicitud rechazada.',
        ]);
    }

    facturas_responder(400, ['status' => 'error', 'mensaje' => 'Acción no válida.']);
} catch (RuntimeException $e) {
    $codigo = in_array($e->getCode(), [403, 404], true) ? $e->getCode() : 422;
    facturas_responder($codigo, ['status' => 'error', 'mensaje' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Historial de facturas: ' . $e->getMessage());
    facturas_responder(500, ['status' => 'error', 'mensaje' => 'No se pudo completar la operación.']);
}