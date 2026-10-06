<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Empleado\EmpleadoRepository;
use App\Empleado\EmpleadoService;

header('Content-Type: application/json; charset=utf-8');
exigir_permiso($conexion, 'operaciones.editar');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    $conexion->close();
    exit;
}

exigir_csrf();

$idHabitacion = filter_input(
    INPUT_POST,
    'id_hab',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$estado = $_POST['estado'] ?? null;
$prioridad = $_POST['prioridad_mantenimiento'] ?? 'No urgente';
$descripcion = $_POST['descripcion_mantenimiento'] ?? '';

if (
    !is_int($idHabitacion)
    || $idHabitacion < 1
    || !is_string($estado)
    || !is_string($prioridad)
    || !is_string($descripcion)
) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos inválidos para actualizar la habitación'], JSON_UNESCAPED_UNICODE);
    $conexion->close();
    exit;
}

try {
    $observacion = EmpleadoService::crearObservacionHabitacion(
        $estado,
        $prioridad,
        $descripcion
    );
    $actualizada = (new EmpleadoRepository())->updateHabitacion(
        $conexion,
        (int) $idHabitacion,
        $estado,
        $observacion
    );

    if (!$actualizada) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'mensaje' => 'La habitación no existe o no se pudo actualizar'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status' => 'exito'], JSON_UNESCAPED_UNICODE);
    }
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Error al actualizar habitación: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo actualizar la habitación'], JSON_UNESCAPED_UNICODE);
}

$conexion->close();
?>
