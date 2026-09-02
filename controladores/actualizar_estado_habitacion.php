<?php
// controladores/actualizar_estado_habitacion.php
session_start();
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
header('Content-Type: application/json');
exigir_permiso($conexion, 'operaciones.editar');

// Verificamos que lleguen los datos correctos
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_hab']) && isset($_POST['estado'])) {
    
    $id_hab = $_POST['id_hab'];
    $nuevo_estado = $_POST['estado'];
    $prioridad_mantenimiento = isset($_POST['prioridad_mantenimiento']) ? $_POST['prioridad_mantenimiento'] : 'No urgente';
    $descripcion_mantenimiento = isset($_POST['descripcion_mantenimiento']) ? $_POST['descripcion_mantenimiento'] : '';
    
    // Preparamos la instrucción UPDATE
    if ($nuevo_estado == "Mantenimiento" && trim($descripcion_mantenimiento) == "") {
        echo json_encode(["status" => "error", "mensaje" => "Complete los campos requeridos."]);
        $conexion->close();
        exit();
    }
    $descripcion_mantenimiento = $nuevo_estado == "Mantenimiento" ? "Prioridad: " . $prioridad_mantenimiento . "\n" . $descripcion_mantenimiento : "";

    $sql = "UPDATE habitacion SET est_hab = ?, obs_hab = ? WHERE cod_hab = ?";
    $stmt = $conexion->prepare($sql);
    
    // "ssi" significa String (el estado), String (observación) e Integer (el ID)
    $stmt->bind_param("ssi", $nuevo_estado, $descripcion_mantenimiento, $id_hab);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "exito"]);
    } else {
        echo json_encode(["status" => "error", "mensaje" => "Error de MySQL: " . $conexion->error]);
    }
    
    $stmt->close();
}
$conexion->close();
?>
