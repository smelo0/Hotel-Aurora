Este archivo (completar_tarea.php) es muy similar al anterior, pero está optimizado específicamente para la acción rápida de marcar una tarea como "Completada" desde la interfaz del empleado o administrador (con manejo transaccional robusto y doble control de excepciones en el rollback).

Aquí tienes tu código integrado con la clase Logger para llevar una auditoría limpia de qué tareas completó cada usuario y detectar cualquier anomalía:

PHP
<?php
// controladores/completar_tarea.php
// Reparacion: Este endpoint ahora responde JSON y permite que la UI confirme exito antes de quitar la tarea.

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';

// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

// Obtenemos el ID del empleado o usuario actual
$idUsuarioLog = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['user_auth']['id_usuario'] ?? 0;

function responder_completar_tarea($payload, $codigo_http = 200) {
    http_response_code($codigo_http);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit();
}

if (!isset($_SESSION['emp_auth']['id_usuario']) && !isset($_SESSION['user_auth']['id_usuario'])) {
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

$id_tarea = isset($_POST['id_tarea']) ? (int) $_POST['id_tarea'] : 0;

if ($id_tarea <= 0) {
    Logger::registrarLog('WARN', 'Intento de completar tarea con ID inválido', [
        'id_usuario' => $idUsuarioLog,
        'id_tarea_enviado' => $_POST['id_tarea'] ?? null
    ]);
    responder_completar_tarea([
        "status" => "error",
        "mensaje" => "ID de tarea invalido"
    ], 422);
}

try {
    $conexion->begin_transaction(); // Transaccion: La tarea solo cambia de estado si MySQL confirma la actualizacion.

    $sql = "UPDATE tarea SET est_tar = 'Completada' WHERE cod_tar = ? AND est_tar = 'Pendiente'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_tarea);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        $conexion->rollback(); // Transaccion: Se revierte si la tarea no existe o ya fue procesada.
        
        Logger::registrarLog('WARN', 'Intento de completar tarea inexistente o que ya no estaba pendiente', [
            'id_usuario' => $idUsuarioLog,
            'id_tarea' => $id_tarea
        ]);

        responder_completar_tarea([
            "status" => "error",
            "mensaje" => "La tarea no existe o ya fue procesada"
        ], 404);
    }

    $stmt->close();
    $conexion->commit(); // Transaccion: Confirmacion atomica para que el frontend actualice la cola.
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
        $conexion->rollback(); // Transaccion: Manejo de errores try/catch en el completado.
    } catch (Throwable $rollback_error) {
        // Transaccion: Si no habia transaccion activa, mantenemos la respuesta JSON controlada.
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