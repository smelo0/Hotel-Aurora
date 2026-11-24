<?php
declare(strict_types=1);

/**
 * Facturas del huésped (solo las de sus propias reservas).
 *   GET accion=listar id_reserva  -> facturas de esa reserva (alojamiento, reembolso, etc.)
 *   GET accion=ver id             -> una factura lista para ver o imprimir
 */

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Billing\FacturaHistorial;

header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function mis_facturas_responder(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit();
}

/** @var mysqli $conexion */
$idUsuario = (int) ($_SESSION['user_auth']['id_usuario'] ?? 0);
if ($idUsuario < 1 || (int) ($_SESSION['user_auth']['rol_usuario'] ?? 0) !== 6) {
    mis_facturas_responder(401, ['status' => 'error', 'mensaje' => 'Inicia sesión con tu cuenta de huésped.']);
}
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    mis_facturas_responder(405, ['status' => 'error', 'mensaje' => 'Método no permitido.']);
}

$accion = (string) ($_GET['accion'] ?? '');

try {
    $repo = new FacturaHistorial($conexion);

    if ($accion === 'listar') {
        $idReserva = filter_var($_GET['id_reserva'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$idReserva) {
            mis_facturas_responder(422, ['status' => 'error', 'mensaje' => 'La reserva no es válida.']);
        }
        mis_facturas_responder(200, ['status' => 'exito', 'facturas' => $repo->facturasDeReserva($idReserva, $idUsuario)]);
    }

    if ($accion === 'ver') {
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) {
            mis_facturas_responder(422, ['status' => 'error', 'mensaje' => 'La factura no es válida.']);
        }
        mis_facturas_responder(200, ['status' => 'exito', 'factura' => $repo->obtenerDeUsuario($id, $idUsuario)]);
    }

    mis_facturas_responder(400, ['status' => 'error', 'mensaje' => 'Acción no válida.']);
} catch (RuntimeException $e) {
    mis_facturas_responder($e->getCode() === 404 ? 404 : 422, ['status' => 'error', 'mensaje' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Facturas del huésped: ' . $e->getMessage());
    mis_facturas_responder(500, ['status' => 'error', 'mensaje' => 'No se pudo cargar la factura.']);
}