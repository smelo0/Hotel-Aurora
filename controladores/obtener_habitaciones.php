<?php
// controladores/obtener_habitaciones.php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';

// Incluimos Composer y el Logger solo para manejo de errores de base de datos
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

header('Content-Type: application/json');
exigir_permiso($conexion, 'operaciones.ver');

// Consulta SQL corregida para soportar los joins con detalle, reservas y usuarios
$sql = "SELECT h.cod_hab, h.num_hab, h.tipo_hab, h.pre_hab, h.est_hab, h.obs_hab,
               u.nom_usu AS huesped_nombre, r.fec_ent_res, r.fec_sal_res, r.est_res
        FROM habitacion h
        LEFT JOIN detalle d ON h.cod_hab = d.cod_hab_det
        LEFT JOIN reservas r ON r.cod_res = d.cod_res_det
            AND r.est_res IN ('Pendiente', 'Confirmada', 'En Casa')
        LEFT JOIN usuario u ON u.id_usu = r.id_usu_res
        WHERE h.est_hab NOT IN ('Mantenimiento', 'Sucia')
        ORDER BY h.num_hab ASC, r.cod_res DESC
        LIMIT 20";

$resultado = $conexion->query($sql);
$habitaciones = [];
$vistos = [];

if ($resultado) {
    if ($resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $id = (int) $fila['cod_hab'];
            if (isset($vistos[$id])) {
                continue;
            }

            $habitaciones[] = [
                'id' => $id,
                'numero' => $fila['num_hab'],
                'tipo' => $fila['tipo_hab'],
                'precio' => $fila['pre_hab'],
                'estado' => $fila['est_hab'],
                'observacion' => $fila['obs_hab'],
                'huesped_nombre' => $fila['huesped_nombre'] ?? null,
                'fec_ent_res' => $fila['fec_ent_res'] ?? null,
                'fec_sal_res' => $fila['fec_sal_res'] ?? null,
                'est_res' => $fila['est_res'] ?? null
            ];

            $vistos[$id] = true;
        }
    }
} else {
    // LOG DE ERROR: Únicamente si la consulta de lectura falla
    Logger::registrarLog('ERROR', 'Fallo al consultar las habitaciones en el panel', [
        'error_db' => $conexion->error
    ]);
}

echo json_encode($habitaciones);
$conexion->close();
?>