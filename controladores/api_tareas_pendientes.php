<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';

header('Content-Type: application/json; charset=utf-8');

$idUsuario = $_SESSION['emp_auth']['id_usuario'] ?? null;
$rolUsuario = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);

// Si no hay sesión válida de admin/gestor, responde 0 sin arrojar error 403 de servidor
if (!$idUsuario || !in_array($rolUsuario, [1, 2], true)) {
    http_response_code(401);
    echo json_encode(['total' => 0, 'status' => 'unauthorized']);
    exit();
}

require_once __DIR__ . '/../configuracion/conexion.php';

/** @var mysqli $conexion */
$sql = "SELECT COUNT(*) as total FROM tarea WHERE est_tar = 'Pendiente'";
$resultado = $conexion->query($sql);

if ($resultado && $fila = $resultado->fetch_assoc()) {
    echo json_encode(['total' => (int) $fila['total'], 'status' => 'exito']);
} else {
    echo json_encode(['total' => 0, 'status' => 'vacio']);
}
$conexion->close();
exit();
