<?php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once '../configuracion/conexion.php';
require_once '../configuracion/permiso.php';

// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

header('Content-Type: application/json; charset=utf-8');
/**@var mysqli $conexion */
exigir_permiso($conexion, 'reservas.eliminar');

// Obtenemos el ID del usuario actual de la sesión para auditoría
$idUsuarioLog = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['user_auth']['id_usuario'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET") {
    /** @var mysqli $conexion */
    $conexion->begin_transaction();
     
    try {
        $cod_res = $_POST['id'] ?? $_GET['id'] ?? '';
        
        if (empty($cod_res)) {
            Logger::registrarLog('WARN', 'Intento de eliminar reserva sin especificar ID', [
                'id_usuario' => $idUsuarioLog
            ]);
            throw new Exception('id_requerido');
        }

        $cod_res = (int) $cod_res;

        // Opcional: Podrías verificar si la reserva realmente existe antes de borrarla, 
        // pero el flujo transaccional igual lo manejará.

        $sql_det = "DELETE FROM detalle WHERE cod_res_det = ?";
        $stmt_det = $conexion->prepare($sql_det);
        $stmt_det->bind_param("i", $cod_res);
        $stmt_det->execute();

        $sql_res = "DELETE FROM reservas WHERE cod_res = ?";
        $stmt_res = $conexion->prepare($sql_res);
        $stmt_res->bind_param("i", $cod_res);
        $stmt_res->execute();

        $conexion->commit();

        // LOG DE ÉXITO (Nivel CRITICAL o INFO con alto valor): Dejar constancia de qué reserva se eliminó y quién lo hizo
        Logger::registrarLog('INFO', 'Reserva eliminada permanentemente del sistema', [
            'id_usuario' => $idUsuarioLog,
            'cod_res_eliminada' => $cod_res
        ]);

        echo json_encode(['status' => 'exito', 'mensaje' => 'Reserva eliminada correctamente']);
        exit();
    } catch (Exception $e) {
        $conexion->rollback();

        // LOG DE ERROR: Si falla el borrado o la transacción
        Logger::registrarLog('ERROR', 'Fallo al intentar eliminar la reserva en base de datos', [
            'id_usuario' => $idUsuarioLog,
            'cod_res' => $_POST['id'] ?? $_GET['id'] ?? null,
            'error_excepcion' => $e->getMessage(),
            'error_db' => $conexion->error
        ]);

        http_response_code(400);
        echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo eliminar la reserva']);
        exit();
    }
}

Logger::registrarLog('WARN', 'Intento de acceso por método no permitido al módulo de eliminar reservas', [
    'id_usuario' => $idUsuarioLog,
    'metodo' => $_SERVER['REQUEST_METHOD']
]);

http_response_code(405);
echo json_encode(['status' => 'error', 'mensaje' => 'Metodo no permitido']);