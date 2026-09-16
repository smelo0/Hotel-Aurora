<?php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once '../configuracion/conexion.php';

function responder_tarea_json($payload, $codigo_http = 200) {
    http_response_code($codigo_http);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit();
}

function es_peticion_ajax_tarea() {
    $acepta_json = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    $es_xml_http_request = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    return $acepta_json || $es_xml_http_request;
}

function redirigir_panel_admin_tarea() {
    $url_anterior = $_SERVER['HTTP_REFERER'] ?? '../interfaz/admin/index_ad.php';
    header("Location: " . $url_anterior);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    if (!es_peticion_ajax_tarea()) {
        redirigir_panel_admin_tarea();
    }

    responder_tarea_json([
        "status" => "error",
        "mensaje" => "Método no permitido"
    ], 405);
}

$id_tarea = isset($_POST['cod_tar']) ? (int) $_POST['cod_tar'] : 0;
$nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';
$estados_permitidos = ['Pendiente', 'Completada', 'Cancelada'];

if ($id_tarea <= 0 || !in_array($nuevo_estado, $estados_permitidos, true)) {
    if (!es_peticion_ajax_tarea()) {
        redirigir_panel_admin_tarea();
    }

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

    if (!es_peticion_ajax_tarea()) {
        redirigir_panel_admin_tarea();
    }

    responder_tarea_json([
        "status" => "exito",
        "mensaje" => "Estado actualizado correctamente",
        "id_tarea" => $id_tarea,
        "estado" => $nuevo_estado
    ]);
} catch (Throwable $error) {
    $conexion->rollback(); // Modificación: Rutina transaccional para actualizar estado de tarea.

    if (es_peticion_ajax_tarea()) {
        responder_tarea_json([
            "status" => "error",
            "mensaje" => "No se pudo actualizar la tarea"
        ], 500);
    }

    redirigir_panel_admin_tarea();
}
?>
