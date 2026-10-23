<?php
declare(strict_types=1);

/**
 * Room service para el personal (interfaz de empleado).
 *
 *   GET  ?accion=pedidos          Pedidos activos y los entregados en las últimas 24 horas (sin datos de cobro).
 *   POST accion=cambiar_estado    Marca un pedido como Pendiente, En preparación o Entregado.
 *
 * Requiere el permiso `roomservice.estado` (o `roomservice.gestionar`).
 * El cobro y la gestión del catálogo siguen siendo exclusivos del panel de administración.
 */

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/RoomService/RoomServiceSchemaMigrator.php';
require_once __DIR__ . '/../src/RoomService/RoomServiceRepository.php';

use App\Logger;
use App\RoomService\RoomServiceRepository;
use App\RoomService\RoomServiceSchemaMigrator;

header('Content-Type: application/json; charset=utf-8');
/** @var mysqli $conexion */

function rse_responder(int $status, string $mensaje, array $datos = []): never
{
    http_response_code($status);
    echo json_encode(
        array_merge(['status' => $status < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

$idEmpleado = (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0);
$rolEmpleado = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);
if ($idEmpleado < 1 || !in_array($rolEmpleado, [1, 2, 3, 4, 5], true)) {
    rse_responder(403, 'Tu sesión no tiene acceso al room service.');
}
if (!usuario_tiene_permiso($conexion, 'roomservice.estado') && !usuario_tiene_permiso($conexion, 'roomservice.gestionar')) {
    rse_responder(403, 'No tienes permiso para ver o actualizar los pedidos de room service.');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
RoomServiceSchemaMigrator::asegurar($conexion);
$repo = new RoomServiceRepository($conexion);
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($metodo === 'GET') {
        $ahora = (string) ($conexion->query('SELECT NOW() AS ahora')->fetch_assoc()['ahora'] ?? date('Y-m-d H:i:s'));
        $instanteAhora = strtotime($ahora) ?: time();
        $limite24h = $instanteAhora - 86400;

        $pedidos = [];
        foreach ($repo->pedidosAdmin(200) as $pedido) {
            $creado = strtotime((string) $pedido['creado_en']) ?: $instanteAhora;
            $estado = (string) $pedido['estado'];
            $activo = in_array($estado, RoomServiceRepository::ESTADOS_ACTIVOS, true);
            if ($estado === 'Cancelado' || (!$activo && $creado < $limite24h)) {
                continue;
            }
            // Solo lo necesario para preparar y entregar: nada de cobros ni correos.
            $pedidos[] = [
                'id_pedido' => (int) $pedido['id_pedido'],
                'estado' => $estado,
                'num_hab' => $pedido['num_hab'],
                'tipo_hab' => (string) $pedido['tipo_hab'],
                'cliente' => (string) $pedido['cliente'],
                'notas' => (string) $pedido['notas'],
                'creado_en' => (string) $pedido['creado_en'],
                'minutos_transcurridos' => max(0, (int) floor(($instanteAhora - $creado) / 60)),
                'minutos_estimados' => (int) $pedido['minutos_estimados'],
                'items' => array_map(static fn(array $i): array => [
                    'nombre' => (string) $i['nombre'],
                    'cantidad' => (int) $i['cantidad'],
                    'tipo' => (string) $i['tipo'],
                ], $pedido['items']),
            ];
        }
        rse_responder(200, 'Pedidos cargados', ['pedidos' => $pedidos]);
    }

    if ($metodo !== 'POST') {
        rse_responder(405, 'Método no permitido.');
    }
    exigir_csrf();

    if ((string) ($_POST['accion'] ?? '') !== 'cambiar_estado') {
        rse_responder(422, 'La acción solicitada no es válida.');
    }
    $idPedido = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $estado = (string) ($_POST['estado'] ?? '');
    if (!$idPedido || !in_array($estado, RoomServiceRepository::ESTADOS_EDITABLES, true)) {
        rse_responder(422, 'El estado del pedido no es válido.');
    }
    if (!$repo->cambiarEstado($idPedido, $estado)) {
        rse_responder(409, 'No se pudo cambiar el estado: el pedido no existe, ya estaba en ese estado o fue cancelado.');
    }
    Logger::registrarLog('INFO', 'Estado de pedido de room service actualizado por personal', [
        'id_usuario' => $idEmpleado, 'id_pedido' => $idPedido, 'estado' => $estado,
    ]);
    rse_responder(200, 'Estado actualizado.', ['id_pedido' => $idPedido, 'estado' => $estado]);
} catch (Throwable $e) {
    Logger::registrarLog('ERROR', 'Fallo en el room service del empleado', [
        'id_usuario' => $idEmpleado, 'error_excepcion' => $e->getMessage(),
    ]);
    rse_responder(500, 'No se pudo completar la operación de room service.');
}