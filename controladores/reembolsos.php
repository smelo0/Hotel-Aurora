<?php
declare(strict_types=1);

/**
 * Reembolsos de reservas pagadas.
 *
 * Huésped (rol 6):
 *   GET  ?accion=cotizar&id_reserva=N        -> cuánto se le devolvería
 *   POST accion=solicitar id_reserva motivo  -> cancela la reserva y crea la solicitud
 * Personal (permiso reembolsos.gestionar):
 *   POST accion=aprobar|rechazar|pagar id_reembolso nota referencia
 */

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Logger;
use App\Reembolso\PoliticaReembolso;
use App\Reembolso\ReembolsoRepository;

header('Content-Type: application/json; charset=utf-8');

function reembolso_responder(int $codigo, array $datos): never
{
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

/** @var mysqli $conexion */
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accion = (string) ($_POST['accion'] ?? $_GET['accion'] ?? '');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$repo = new ReembolsoRepository($conexion);

$esHuesped = (int) ($_SESSION['user_auth']['id_usuario'] ?? 0) > 0
    && (int) ($_SESSION['user_auth']['rol_usuario'] ?? 0) === 6;

// ---------- Huésped ----------
if (in_array($accion, ['cotizar', 'solicitar'], true)) {
    if (!$esHuesped) {
        reembolso_responder(401, ['status' => 'error', 'mensaje' => 'Inicia sesión con tu cuenta de huésped.']);
    }
    $idUsuario = (int) $_SESSION['user_auth']['id_usuario'];
    $idReserva = filter_var($_POST['id_reserva'] ?? $_GET['id_reserva'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$idReserva) {
        reembolso_responder(422, ['status' => 'error', 'mensaje' => 'La reserva no es válida.']);
    }

    if ($accion === 'cotizar' && $metodo === 'GET') {
        $c = $repo->cotizar($idReserva, $idUsuario);
        reembolso_responder(200, [
            'status' => 'exito',
            'elegible' => $c['elegible'],
            'mensaje' => $c['motivo'],
            'pagado' => $c['pagado'],
            'porcentaje' => $c['porcentaje'],
            'monto' => $c['monto'],
            'politica' => PoliticaReembolso::descripcion(),
        ]);
    }

    if ($accion === 'solicitar' && $metodo === 'POST') {
        exigir_csrf();
        try {
            $id = $repo->solicitar($idReserva, $idUsuario, (string) ($_POST['motivo'] ?? ''));
        } catch (RuntimeException $e) {
            reembolso_responder(409, ['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
        Logger::registrarLog('INFO', 'Reembolso solicitado por el huésped', [
            'id_usuario' => $idUsuario, 'cod_res' => $idReserva, 'id_reembolso' => $id,
        ]);
        reembolso_responder(200, [
            'status' => 'exito',
            'mensaje' => 'Tu reserva fue cancelada y la solicitud de reembolso quedó registrada. El hotel la revisará y te contactará.',
        ]);
    }
    reembolso_responder(405, ['status' => 'error', 'mensaje' => 'Método no permitido.']);
}

// ---------- Personal ----------
if (in_array($accion, ['aprobar', 'rechazar', 'pagar', 'crear'], true)) {
    if ($metodo !== 'POST') {
        reembolso_responder(405, ['status' => 'error', 'mensaje' => 'Método no permitido.']);
    }
    exigir_permiso($conexion, 'reembolsos.gestionar');
    exigir_csrf();

    if ($accion === 'crear') {
        $idReserva = filter_var($_POST['id_reserva'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $porcentaje = filter_var($_POST['porcentaje'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        if (!$idReserva || !$porcentaje) {
            reembolso_responder(422, ['status' => 'error', 'mensaje' => 'Elige una reserva y un porcentaje entre 1 y 100.']);
        }
        $idAdminCrear = (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0);
        try {
            $mensaje = $repo->crearComoAdmin($idReserva, $idAdminCrear, $porcentaje, (string) ($_POST['motivo'] ?? ''), (string) ($_POST['referencia'] ?? ''));
        } catch (RuntimeException $e) {
            reembolso_responder(422, ['status' => 'error', 'mensaje' => $e->getMessage()]);
        }
        Logger::registrarLog('INFO', 'Reembolso creado por el personal', ['id_usuario' => $idAdminCrear, 'cod_res' => $idReserva, 'porcentaje' => $porcentaje]);
        reembolso_responder(200, ['status' => 'exito', 'mensaje' => $mensaje]);
    }

    $idReembolso = filter_var($_POST['id_reembolso'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$idReembolso) {
        reembolso_responder(422, ['status' => 'error', 'mensaje' => 'La solicitud no es válida.']);
    }
    $idAdmin = (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0);

    try {
        $mensaje = $repo->resolver(
            $idReembolso,
            $accion,
            $idAdmin,
            (string) ($_POST['nota'] ?? ''),
            (string) ($_POST['referencia'] ?? '')
        );
    } catch (RuntimeException $e) {
        reembolso_responder(422, ['status' => 'error', 'mensaje' => $e->getMessage()]);
    }
    Logger::registrarLog('INFO', 'Reembolso ' . $accion, ['id_usuario' => $idAdmin, 'id_reembolso' => $idReembolso]);
    reembolso_responder(200, ['status' => 'exito', 'mensaje' => $mensaje]);
}

reembolso_responder(400, ['status' => 'error', 'mensaje' => 'Acción no válida.']);