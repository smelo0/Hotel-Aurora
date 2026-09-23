<?php
// controladores/obtener_tareas.php
// Reparacion: Endpoint unificado para que empleado y administrador lean la misma cola desde MySQL.

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';

// Incluimos Composer y el Logger solo para manejo de errores de base de datos
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

header('Content-Type: application/json; charset=utf-8');

function limpiar_nombre_creador_api_tareas($nombre, $rol) {
    // Correccion: Formato de nombre y rol ajustado a [Nombre] - [Rol], evitando duplicar el rol si viene pegado al nombre.
    $nombre_limpio = trim((string) $nombre);
    $rol_limpio = trim((string) $rol);

    if ($rol_limpio !== '') {
        $nombre_limpio = preg_replace('/\s+-?\s*' . preg_quote($rol_limpio, '/') . '$/iu', '', $nombre_limpio);
    }

    return trim($nombre_limpio) !== '' ? trim($nombre_limpio) : 'Usuario';
}

try {
    // Reparacion: Se ordena por cat_tar porque prioridad_tar en el SQL es enum Baja/Media/Alta.
    // Transaccion: Lectura consistente para que empleado y administrador vean la misma cola sin recargar.
    $sql = "SELECT t.cod_tar, t.tit_tar, t.cat_tar, t.des_tar, t.prioridad_tar, t.fec_tar, u.nom_usu, u.cod_rol_usu, r.des_rol
            FROM tarea t
            INNER JOIN usuario u ON t.cod_usu_tar = u.id_usu
            LEFT JOIN rol r ON u.cod_rol_usu = r.cod_rol
            WHERE t.est_tar = 'Pendiente'
            ORDER BY CASE WHEN t.cat_tar = 'URGENTE' THEN 1 WHEN t.cat_tar = 'LIMPIEZA' THEN 2 ELSE 3 END, t.fec_tar ASC";

    $resultado = $conexion->query($sql);
    $tareas = [];

    if ($resultado && $resultado->num_rows > 0) {
        while ($fila = $resultado->fetch_assoc()) {
            $rol_nombre = $fila['des_rol'] ?: 'Personal';
            $creador_nombre = limpiar_nombre_creador_api_tareas($fila['nom_usu'], $rol_nombre);

            $tareas[] = [
                "id" => $fila['cod_tar'],
                "titulo" => $fila['tit_tar'],
                "categoria" => $fila['cat_tar'],
                "descripcion" => $fila['des_tar'],
                "prioridad" => $fila['prioridad_tar'],
                "fecha" => $fila['fec_tar'],
                "creador" => $creador_nombre,
                "creador_formateado" => $creador_nombre . " - " . $rol_nombre, // Correccion: Siempre disponible como [Nombre] - [Rol].
                "rol" => $fila['cod_rol_usu'],
                "rol_nombre" => $rol_nombre
            ];
        }
    }

    echo json_encode($tareas);
    $conexion->close();
} catch (Throwable $error) {
    // LOG DE ERROR: Si falla la lectura o la conexión a la base de datos
    Logger::registrarLog('ERROR', 'Fallo al obtener la cola de tareas pendientes', [
        'error_mensaje' => $error->getMessage()
    ]);

    // Transaccion: Manejo de errores try/catch para que fetch reciba JSON y no HTML roto.
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "mensaje" => "No se pudieron obtener las tareas"
    ]);
}
?>