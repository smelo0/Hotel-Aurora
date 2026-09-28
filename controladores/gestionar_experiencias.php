<?php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';

header('Content-Type: application/json; charset=utf-8');

function responder_experiencias($status, $mensaje, $datos = []) {
    http_response_code($status);
    echo json_encode(array_merge(['status' => $status < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

$conexion->query(
    "CREATE TABLE IF NOT EXISTS experiencias (
        id INT AUTO_INCREMENT PRIMARY KEY,
        categoria VARCHAR(50) NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if (!usuario_tiene_permiso($conexion, 'experiencias.ver')) {
    responder_experiencias(403, 'No tienes permiso para consultar las experiencias');
}

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'listar';

if ($metodo === 'GET' && $accion === 'listar') {
    $experiencias = [];
    $resultado = $conexion->query('SELECT id, categoria, nombre, descripcion, fecha_creacion FROM experiencias ORDER BY id ASC');
    while ($fila = $resultado->fetch_assoc()) {
        $fila['id'] = (int) $fila['id'];
        $experiencias[] = $fila;
    }

    responder_experiencias(200, 'Experiencias cargadas', [
        'experiencias' => $experiencias,
        'puede_gestionar' => usuario_tiene_permiso($conexion, 'experiencias.gestionar'),
    ]);
}

if ($metodo !== 'POST') {
    responder_experiencias(405, 'Método no permitido');
}

exigir_csrf();

if (!usuario_tiene_permiso($conexion, 'experiencias.gestionar')) {
    responder_experiencias(403, 'No tienes permiso para gestionar las experiencias');
}

if ($accion === 'guardar') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $categoria = trim($_POST['categoria'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($categoria === '' || mb_strlen($categoria) > 50) {
        responder_experiencias(422, 'La categoría es obligatoria y debe tener máximo 50 caracteres');
    }
    if ($nombre === '' || mb_strlen($nombre) > 150) {
        responder_experiencias(422, 'El nombre es obligatorio y debe tener máximo 150 caracteres');
    }
    if ($descripcion === '') {
        responder_experiencias(422, 'La descripción es obligatoria');
    }

    try {
        if ($id) {
            $stmt = $conexion->prepare('UPDATE experiencias SET categoria = ?, nombre = ?, descripcion = ? WHERE id = ?');
            $stmt->bind_param('sssi', $categoria, $nombre, $descripcion, $id);
            $stmt->execute();
        } else {
            $stmt = $conexion->prepare('INSERT INTO experiencias (categoria, nombre, descripcion) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $categoria, $nombre, $descripcion);
            $stmt->execute();
            $id = $stmt->insert_id;
        }

        responder_experiencias(200, 'Experiencia guardada correctamente', ['id' => (int) $id]);
    } catch (Throwable $error) {
        responder_experiencias(500, 'No se pudo guardar la experiencia');
    }
}

if ($accion === 'eliminar') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) {
        responder_experiencias(422, 'Experiencia no válida');
    }

    $stmt = $conexion->prepare('DELETE FROM experiencias WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    responder_experiencias(
        $stmt->affected_rows === 1 ? 200 : 404,
        $stmt->affected_rows === 1 ? 'Experiencia eliminada correctamente' : 'Experiencia no encontrada'
    );
}

responder_experiencias(400, 'Acción no válida');