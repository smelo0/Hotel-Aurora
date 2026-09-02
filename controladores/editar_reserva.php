<?php
session_start();
require_once '../configuracion/conexion.php';
require_once '../configuracion/permiso.php';
header('Content-Type: application/json; charset=utf-8');
/**@var mysqli $conexion */
exigir_permiso($conexion, 'reservas.editar');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conexion->begin_transaction();

    try {
        $cod_res = $_POST['cod_res'] ?? '';
        $habitacion = $_POST['habitacion'] ?? '';
        $estado = $_POST['estado'] ?? '';
        $notas = $_POST['notas'] ?? '';

        if (empty($cod_res)) {
            throw new Exception('id_requerido');
        }

        $cod_res = (int) $cod_res;
        $hab_final = empty(trim($habitacion)) ? NULL : trim($habitacion);

        $sql_res = "UPDATE reservas SET est_res = ?, not_res = ? WHERE cod_res = ?";
        $stmt_res = $conexion->prepare($sql_res);
        $stmt_res->bind_param("ssi", $estado, $notas, $cod_res);
        $stmt_res->execute();

        $sql_det = "UPDATE detalle SET cod_hab_det = ? WHERE cod_res_det = ?";
        $stmt_det = $conexion->prepare($sql_det);
        $stmt_det->bind_param("si", $hab_final, $cod_res);
        $stmt_det->execute();

        $sql_actualizada = "SELECT r.cod_res, u.nom_usu, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res, d.cod_hab_det
                            FROM reservas r
                            INNER JOIN usuario u ON r.id_usu_res = u.id_usu
                            LEFT JOIN detalle d ON r.cod_res = d.cod_res_det
                            WHERE r.cod_res = ?
                            LIMIT 1";
        $stmt_actualizada = $conexion->prepare($sql_actualizada);
        $stmt_actualizada->bind_param("i", $cod_res);
        $stmt_actualizada->execute();
        $res_actualizada = $stmt_actualizada->get_result();
        $reserva_actualizada = $res_actualizada->fetch_assoc();

        if (!$reserva_actualizada) {
            throw new Exception('reserva_no_encontrada');
        }

        $conexion->commit();
        echo json_encode([
            'status' => 'exito',
            'mensaje' => 'Reserva actualizada correctamente',
            'reserva' => [
                'cod_res' => (int) $reserva_actualizada['cod_res'],
                'nom_usu' => $reserva_actualizada['nom_usu'],
                'fec_ent_res' => $reserva_actualizada['fec_ent_res'],
                'fec_sal_res' => $reserva_actualizada['fec_sal_res'],
                'est_res' => $reserva_actualizada['est_res'],
                'not_res' => $reserva_actualizada['not_res'] ?? '',
                'cod_hab_det' => $reserva_actualizada['cod_hab_det']
            ]
        ]);
        exit();
    } catch (Exception $e) {
        $conexion->rollback();
        http_response_code(400);
        echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo actualizar la reserva']);
        exit();
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'mensaje' => 'Metodo no permitido']);
