<?php
session_start();
require_once '../configuracion/conexion.php';
require_once '../configuracion/permiso.php';
header('Content-Type: application/json; charset=utf-8');
/**@var mysqli $conexion */
exigir_permiso($conexion, 'reservas.eliminar');

if ($_SERVER["REQUEST_METHOD"] == "POST" || $_SERVER["REQUEST_METHOD"] == "GET") {
    /** @var mysqli $conexion */
    $conexion->begin_transaction();
     
    try {
        $cod_res = $_POST['id'] ?? $_GET['id'] ?? '';
        
        if (empty($cod_res)) {
            throw new Exception('id_requerido');
        }

        $cod_res = (int) $cod_res;

        $sql_det = "DELETE FROM detalle WHERE cod_res_det = ?";
        $stmt_det = $conexion->prepare($sql_det);
        $stmt_det->bind_param("i", $cod_res);
        $stmt_det->execute();

        $sql_res = "DELETE FROM reservas WHERE cod_res = ?";
        $stmt_res = $conexion->prepare($sql_res);
        $stmt_res->bind_param("i", $cod_res);
        $stmt_res->execute();

        $conexion->commit();
        echo json_encode(['status' => 'exito', 'mensaje' => 'Reserva eliminada correctamente']);
        exit();
    } catch (Exception $e) {
        $conexion->rollback();
        http_response_code(400);
        echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo eliminar la reserva']);
        exit();
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'mensaje' => 'Metodo no permitido']);