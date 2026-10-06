<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Empleado\EmpleadoRepository;

header('Content-Type: application/json; charset=utf-8');
exigir_permiso($conexion, 'operaciones.ver');

try {
    $habitaciones = (new EmpleadoRepository())->fetchHabitaciones($conexion);
    echo json_encode($habitaciones, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    error_log('Error al obtener habitaciones: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(
        ['status' => 'error', 'mensaje' => 'No se pudieron obtener las habitaciones'],
        JSON_UNESCAPED_UNICODE
    );
}

$conexion->close();
?>
