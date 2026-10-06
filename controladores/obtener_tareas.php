<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Empleado\EmpleadoRepository;

header('Content-Type: application/json; charset=utf-8');
if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario'])
    || !in_array((int) $_SESSION['emp_auth']['rol_usuario'], [1, 2, 3, 4, 5], true)
) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'No estás autorizado'], JSON_UNESCAPED_UNICODE);
    $conexion->close();
    exit();
}

require_once __DIR__ . '/../configuracion/conexion.php';

try {
    echo json_encode(
        (new EmpleadoRepository())->fetchTareasPendientes($conexion),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
} catch (\Throwable $error) {
    error_log('Error al obtener tareas pendientes: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'mensaje' => 'No se pudieron obtener las tareas',
    ], JSON_UNESCAPED_UNICODE);
}

$conexion->close();
?>
