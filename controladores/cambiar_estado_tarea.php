<?php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once '../configuracion/conexion.php';

// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

// Obtenemos el ID del usuario actual de la sesión para auditoría
$idUsuarioLog = $_SESSION['user_auth']['id_usuario'] ?? $_SESSION['emp_auth']['id_usuario'] ?? 0;

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
    Logger::registrarLog('WARN', 'Intento de acceso por método no permitido al actualizar tarea', [
        'id_usuario' => $idUsuarioLog,
        'metodo' => $_SERVER['REQUEST_METHOD']
    ]);

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
    Logger::registrarLog('WARN', 'Intento de actualizar tarea con datos inválidos', [
        'id_usuario' => $idUsuarioLog,
        'cod_tar' => $id_tarea,
        'nuevo_estado' => $nuevo_estado
    ]);

    if (!es_peticion_ajax_tarea()) {
        redirigir_panel_admin_tarea();
    }

    responder_tarea_json([
        "status" => "error",
        "mensaje" => "Datos inválidos para actualizar la tarea"
    ], 422);
}

try {
    $conexion->begin_transaction();

    $sql = "UPDATE tarea SET est_tar = ? WHERE cod_tar = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nuevo_estado, $id_tarea);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        $conexion->rollback();
        
        Logger::registrarLog('WARN', 'Intento de actualizar tarea inexistente o sin cambios', [
            'id_usuario' => $idUsuarioLog,
            'cod_tar' => $id_tarea
        ]);

        responder_tarea_json([
            "status" => "error",
            "mensaje" => "La tarea no existe o ya fue procesada"
        ], 404);
    }

    $stmt->close();
    $conexion->commit();
    $conexion->close();

    // LOG DE ÉXITO: Tarea actualizada correctamente con su contexto
    Logger::registrarLog('INFO', 'Estado de tarea actualizado con éxito', [
        'id_usuario' => $idUsuarioLog,
        'cod_tar' => $id_tarea,
        'nuevo_estado' => $nuevo_estado
    ]);

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
    $conexion->rollback();

    // LOG DE ERROR: Captura cualquier excepción de base de datos o sistema
    Logger::registrarLog('ERROR', 'Excepción crítica al actualizar estado de tarea', [
        'id_usuario' => $idUsuarioLog,
        'cod_tar' => $id_tarea,
        'error_mensaje' => $error->getMessage()
    ]);

    if (es_peticion_ajax_tarea()) {
        responder_tarea_json([
            "status" => "error",
            "mensaje" => "No se pudo actualizar la tarea"
        ], 500);
    }

    redirigir_panel_admin_tarea();
}
?>