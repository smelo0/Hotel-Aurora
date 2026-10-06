<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Empleado\EmpleadoRepository;
use App\Logger;

$idUsuarioLog = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['user_auth']['id_usuario'] ?? 0;

function responder_completar_tarea($payload, $codigo_http = 200) {
    http_response_code($codigo_http);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit();
}

if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario'])
    || !in_array((int) $_SESSION['emp_auth']['rol_usuario'], [1, 2, 3, 4, 5], true)
) {
    Logger::registrarLog('WARN', 'Intento no autorizado de completar tarea sin sesión activa');
    responder_completar_tarea([
        "status" => "error",
        "mensaje" => "No estas autorizado"
    ], 401);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    Logger::registrarLog('WARN', 'Intento de acceso por método no permitido al completar tarea', [
        'id_usuario' => $idUsuarioLog,
        'metodo' => $_SERVER['REQUEST_METHOD']
    ]);
    responder_completar_tarea([
        "status" => "error",
        "mensaje" => "Metodo no permitido"
    ], 405);
}

exigir_csrf();

$id_tarea = filter_input(
    INPUT_POST,
    'id_tarea',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!is_int($id_tarea) || $id_tarea < 1) {
    Logger::registrarLog('WARN', 'Intento de completar tarea con ID inválido', [
        'id_usuario' => $idUsuarioLog,
        'id_tarea_enviado' => $_POST['id_tarea'] ?? null
    ]);
    responder_completar_tarea([
        "status" => "error",
        "mensaje" => "ID de tarea invalido"
    ], 422);
}

require_once __DIR__ . '/../configuracion/conexion.php';

try {
    if (!$conexion->begin_transaction()) {
        throw new RuntimeException('No se pudo iniciar la transacción para completar la tarea.');
    }
    if (!(new EmpleadoRepository())->completePendingTask($conexion, $id_tarea)) {
        $conexion->rollback();
        
        Logger::registrarLog('WARN', 'Intento de completar tarea inexistente o que ya no estaba pendiente', [
            'id_usuario' => $idUsuarioLog,
            'id_tarea' => $id_tarea
        ]);

        responder_completar_tarea([
            "status" => "error",
            "mensaje" => "La tarea no existe o ya fue procesada"
        ], 404);
    }

    if (!$conexion->commit()) {
        throw new RuntimeException('No se pudo confirmar la finalización de la tarea.');
    }
    $conexion->close();

    // LOG DE ÉXITO: Tarea marcada como completada correctamente
    Logger::registrarLog('INFO', 'Tarea completada exitosamente', [
        'id_usuario' => $idUsuarioLog,
        'id_tarea' => $id_tarea
    ]);

    responder_completar_tarea([
        "status" => "exito",
        "mensaje" => "Tarea completada correctamente",
        "id_tarea" => $id_tarea
    ], 200);
} catch (Throwable $error) {
    try {
        $conexion->rollback();
    } catch (Throwable $rollback_error) {
        error_log('No se pudo revertir la finalización de la tarea: ' . $rollback_error->getMessage());
    }

    // LOG DE ERROR: Captura de excepción crítica en base de datos o servidor
    Logger::registrarLog('ERROR', 'Excepción crítica al intentar completar tarea', [
        'id_usuario' => $idUsuarioLog,
        'id_tarea' => $id_tarea,
        'error_mensaje' => $error->getMessage()
    ]);

    responder_completar_tarea([
        "status" => "error",
        "mensaje" => "No se pudo completar la tarea"
    ], 500);
}
?>