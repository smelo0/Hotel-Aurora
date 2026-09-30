<?php
// Este archivo NO tiene HTML, solo devuelve un número para el AJAX
require_once __DIR__ . '/../includes/sesion_seguridad.php';

$rolUsuario = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);
if (!isset($_SESSION['emp_auth']['id_usuario']) || !in_array($rolUsuario, [1, 2], true)) {
    http_response_code(403);
    echo '0';
    exit;
}

require_once __DIR__ . '/../configuracion/conexion.php';

// Contamos solo las tareas que dicen "Pendiente"
$sql = "SELECT COUNT(*) as total FROM tarea WHERE est_tar = 'Pendiente'";
$resultado = $conexion->query($sql);

if ($fila = $resultado->fetch_assoc()) {
    echo $fila['total']; // Devuelve el número (ej: 8, 12, 0)
} else {
    echo "0";
}
?>