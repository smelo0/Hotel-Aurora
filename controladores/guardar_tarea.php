<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Empleado\EmpleadoRepository;
use App\Empleado\EmpleadoService;
use App\Logger;

header('Content-Type: application/json; charset=utf-8');

if (
    !isset($_SESSION['emp_auth']['id_usuario'], $_SESSION['emp_auth']['rol_usuario'])
    || !in_array((int) $_SESSION['emp_auth']['rol_usuario'], [1, 2, 3, 4, 5], true)
) {
    Logger::registrarLog('WARNING', 'Intento de guardar tarea sin sesión activa', []);
    http_response_code(401);
    echo json_encode(['status' => 'error', 'mensaje' => 'No estás autorizado'], JSON_UNESCAPED_UNICODE);
    exit();
}

require_once __DIR__ . '/../configuracion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Logger::registrarLog('WARNING', 'Intento de acceso por método no permitido al guardar tarea', ['metodo' => $_SERVER['REQUEST_METHOD']]);
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit();
}

exigir_csrf();

$tituloRaw = $_POST['titulo'] ?? null;
$categoriaRaw = $_POST['categoria'] ?? null;
$descripcionRaw = $_POST['descripcion'] ?? null;
if (!is_string($tituloRaw) || !is_string($categoriaRaw) || !is_string($descripcionRaw)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => 'Datos inválidos para crear la tarea'], JSON_UNESCAPED_UNICODE);
    exit();
}

$titulo = trim($tituloRaw);
$categoria = trim($categoriaRaw);
$descripcion = trim($descripcionRaw);
$id_sesion = (int) $_SESSION['emp_auth']['id_usuario'];
$panelOrigen = $_POST['panel_origen'] ?? '';
$panelOrigen = is_string($panelOrigen) && in_array($panelOrigen, ['empleado', 'admin'], true)
    ? $panelOrigen
    : 'empleado';
$fecha = date('Y-m-d H:i:s');

try {
    EmpleadoService::validarDatosTarea($titulo, $categoria, $descripcion);
} catch (\InvalidArgumentException $error) {
    Logger::registrarLog('WARNING', 'Intento de guardar tarea con campos incompletos', ['usuario_id' => $id_sesion]);
    http_response_code(422);
    echo json_encode(['status' => 'error', 'mensaje' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
    exit();
}

$repository = new EmpleadoRepository();
try {
    if (!$conexion->begin_transaction()) {
        throw new RuntimeException('No se pudo iniciar la transacción para crear la tarea.');
    }
    $datos_usuario = $repository->fetchCreadorTarea($conexion, $id_sesion);
    if (!$datos_usuario) {
        $conexion->rollback();
        Logger::registrarLog('WARNING', 'Fallo al guardar tarea: No se pudo confirmar el creador en la base de datos', ['usuario_id' => $id_sesion]);
        http_response_code(422);
        echo json_encode(['status' => 'error', 'mensaje' => 'No se pudo confirmar el creador de la tarea'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $rolNombre = trim((string) ($datos_usuario['des_rol'] ?? '')) ?: 'Personal';
    $nombreCreador = trim((string) ($datos_usuario['nom_usu'] ?? '')) ?: 'Usuario';
    $nombreCreador = trim((string) preg_replace(
        '/\s+-?\s*' . preg_quote($rolNombre, '/') . '$/iu',
        '',
        $nombreCreador
    )) ?: 'Usuario';

    $id_tarea = $repository->insertTarea($conexion, $titulo, $categoria, $descripcion, $fecha, $id_sesion);
    if (!$conexion->commit()) {
        throw new RuntimeException('No se pudo confirmar la creación de la tarea.');
    }

    Logger::registrarLog('INFO', 'Tarea guardada exitosamente', ['usuario_id' => $id_sesion, 'tarea_id' => $id_tarea, 'titulo' => $titulo]);

    http_response_code(201);
    echo json_encode([
        'status' => 'exito',
        'mensaje' => 'Tarea guardada correctamente en la Base de Datos',
        'id_tarea' => $id_tarea,
        'tarea' => [
            'id' => $id_tarea,
            'titulo' => $titulo,
            'categoria' => $categoria,
            'descripcion' => $descripcion,
            'estado' => 'Pendiente',
            'fecha' => $fecha,
            'creador' => $nombreCreador,
            'creador_formateado' => $nombreCreador . ' - ' . $rolNombre,
            'rol' => $datos_usuario['cod_rol_usu'],
            'rol_nombre' => $rolNombre,
            'panel_origen' => $panelOrigen,
            'autor_validado_por' => 'sesion_php',
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (Throwable $error) {
    try {
        $conexion->rollback();
    } catch (Throwable $rollback_error) {
        error_log('No se pudo revertir la creación de la tarea: ' . $rollback_error->getMessage());
    }
    
    Logger::registrarLog('ERROR', 'Excepción crítica al guardar tarea', ['usuario_id' => $id_sesion, 'error_db' => $error->getMessage()]);
    
    http_response_code(500);
    echo json_encode(['status' => 'error', 'mensaje' => 'Hubo un error al guardar la tarea'], JSON_UNESCAPED_UNICODE);
}

$conexion->close();
?>