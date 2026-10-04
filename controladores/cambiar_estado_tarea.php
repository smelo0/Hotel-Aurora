<?php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
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

$id_tarea = isset($_POST['cod_tar']) ? (int) $_POST['cod_tar'] : 0;
$nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';
$estados_permitidos = ['Pendiente', 'Completada', 'Cancelada'];

if ($id_tarea <= 0 || !in_array($nuevo_estado, $estados_permitidos, true)) {
    responder_tarea_json([
        "status" => "error",
        "mensaje" => "Datos inválidos para actualizar la tarea"
    ], 422);
}

try {
    $conexion->begin_transaction(); // Modificación: Rutina transaccional para actualizar estado de tarea.

    $sql = "UPDATE tarea SET est_tar = ? WHERE cod_tar = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $id_tarea);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        $conexion->rollback(); // Modificación: Rutina transaccional para actualizar estado de tarea.
        responder_tarea_json([
            "status" => "error",
            "mensaje" => "La tarea no existe o ya fue procesada"
        ], 404);
    }

    $conexion->commit(); // Modificación: Rutina transaccional para actualizar estado de tarea.
    $stmt->close();
    $conexion->close();

    responder_tarea_json([
        "status" => "exito",
        "mensaje" => "Estado actualizado correctamente",
        "id_tarea" => $id_tarea,
        "estado" => $nuevo_estado
    ]);
} catch (Throwable $error) {
    $conexion->rollback(); // Modificación: Rutina transaccional para actualizar estado de tarea.
    responder_tarea_json([
        "status" => "error",
        "mensaje" => "No se pudo actualizar la tarea"
    ], 500);
}
?>
