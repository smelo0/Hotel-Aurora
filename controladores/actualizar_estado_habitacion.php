
<?php
// controladores/actualizar_estado_habitacion.php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';

// Asegúrate de incluir el autoloader y usar la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

header('Content-Type: application/json');
exigir_permiso($conexion, 'operaciones.editar');

// Obtenemos el ID del usuario que está realizando la acción (para auditoría en el log)
$idUsuarioLog = $_SESSION['user_auth']['id_usuario'] ?? $_SESSION['emp_auth']['id_usuario'] ?? 0;

// Verificamos que lleguen los datos correctos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_hab']) && isset($_POST['estado'])) {
    
    $id_hab = $_POST['id_hab'];
    $nuevo_estado = $_POST['estado'];
    $prioridad_mantenimiento = isset($_POST['prioridad_mantenimiento']) ? $_POST['prioridad_mantenimiento'] : 'No urgente';
    $descripcion_mantenimiento = isset($_POST['descripcion_mantenimiento']) ? $_POST['descripcion_mantenimiento'] : '';
    
    // Preparamos la instrucción UPDATE
    if ($nuevo_estado == "Mantenimiento" && trim($descripcion_mantenimiento) == "") {
        // LOG: Advertencia por intento de mantenimiento sin descripción obligatoria
        Logger::registrarLog('WARN', 'Intento de enviar habitación a mantenimiento sin descripción', [
            'id_usuario' => $idUsuarioLog,
            'habitacion_id' => $id_hab
        ]);

        echo json_encode(["status" => "error", "mensaje" => "Complete los campos requeridos."]);
        $conexion->close();
        exit();
    }
    $descripcion_mantenimiento = $nuevo_estado == "Mantenimiento" ? "Prioridad: " . $prioridad_mantenimiento . "\n" . $descripcion_mantenimiento : "";

    $sql = "UPDATE habitacion SET est_hab = ?, obs_hab = ? WHERE cod_hab = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        Logger::registrarLog('ERROR', 'Fallo al preparar consulta SQL para actualizar estado de habitación', [
            'id_usuario' => $idUsuarioLog,
            'error_db' => $conexion->error
        ]);
        echo json_encode(["status" => "error", "mensaje" => "Error de preparación: " . $conexion->error]);
        $conexion->close();
        exit();
    }

    // "ssi" significa String (el estado), String (observación) e Integer (el ID)
    $stmt->bind_param("ssi", $nuevo_estado, $descripcion_mantenimiento, $id_hab);
    
    if ($stmt->execute()) {
        // LOG DE ÉXITO: El estado de la habitación cambió correctamente
        Logger::registrarLog('INFO', 'Estado de habitación actualizado con éxito', [
            'id_usuario' => $idUsuarioLog,
            'habitacion_id' => $id_hab,
            'nuevo_estado' => $nuevo_estado
        ]);

        echo json_encode(["status" => "exito"]);
    } else {
        // LOG DE ERROR: Falló la ejecución de MySQL
        Logger::registrarLog('ERROR', 'Fallo al ejecutar la actualización del estado de la habitación', [
            'id_usuario' => $idUsuarioLog,
            'habitacion_id' => $id_hab,
            'error_db' => $stmt->error
        ]);

        echo json_encode(["status" => "error", "mensaje" => "Error de MySQL: " . $stmt->error]);
    }
    
    $stmt->close();
}
$conexion->close();
?>