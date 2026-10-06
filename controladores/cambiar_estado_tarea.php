<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Empleado\EmpleadoRepository;

header('Content-Type: application/json; charset=utf-8');

function responder_tarea_json($payload, $codigo_http = 200) {
    http_response_code($codigo_http);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
}

if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario'])
    || !in_array((int) $_SESSION['emp_auth']['rol_usuario'], [1, 2, 3, 4, 5], true)
) {
    responder_tarea_json(["status" => "error", "mensaje" => "No estás autorizado"], 401);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder_tarea_json([
        "status" => "error",
        "mensaje" => "Método no permitido"
    ], 405);
}

exigir_csrf();

require_once __DIR__ . '/../configuracion/conexion.php';

$estadoRaw = $_POST['nuevo_estado'] ?? null;
$id_tarea = filter_input(
    INPUT_POST,
    'cod_tar',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$nuevo_estado = is_string($estadoRaw) ? trim($estadoRaw) : '';
$estados_permitidos = ['Pendiente', 'Completada', 'Cancelada'];

if (!is_int($id_tarea) || $id_tarea < 1 || !in_array($nuevo_estado, $estados_permitidos, true)) {
    responder_tarea_json([
        "status" => "error",
        "mensaje" => "Datos inválidos para actualizar la tarea"
    ], 422);
}

try {
    if (!$conexion->begin_transaction()) {
        throw new RuntimeException('No se pudo iniciar la transacción para actualizar la tarea.');
    }
    if (!(new EmpleadoRepository())->updateTaskStatus($conexion, (int) $id_tarea, $nuevo_estado)) {
        $conexion->rollback();
        responder_tarea_json([
            "status" => "error",
            "mensaje" => "La tarea no existe o ya fue procesada"
        ], 404);
    }

    if (!$conexion->commit()) {
        throw new RuntimeException('No se pudo confirmar la actualización de la tarea.');
    }
    $conexion->close();

    responder_tarea_json([
        "status" => "exito",
        "mensaje" => "Estado actualizado correctamente",
        "id_tarea" => $id_tarea,
        "estado" => $nuevo_estado
    ]);
} catch (Throwable $error) {
    try {
        $conexion->rollback();
    } catch (Throwable $rollbackError) {
        error_log('No se pudo revertir la actualización de tarea: ' . $rollbackError->getMessage());
    }
    error_log('Error al actualizar estado de tarea: ' . $error->getMessage());
    responder_tarea_json([
        "status" => "error",
        "mensaje" => "No se pudo actualizar la tarea"
    ], 500);
}
?>
