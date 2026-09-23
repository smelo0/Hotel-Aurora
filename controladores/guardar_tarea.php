<?php
// controladores/guardar_tarea.php

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';

// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

header('Content-Type: application/json; charset=utf-8');

// Obtenemos el ID del empleado actual de la sesión para auditoría
$idUsuarioLog = $_SESSION['emp_auth']['id_usuario'] ?? 0;

function obtener_nombre_rol_tarea($codigo_rol) {
    // Corrección: Formato de nombre y rol ajustado a [Nombre] - [Rol].
    $roles = [
        1 => 'Admin',
        2 => 'Gestor',
        3 => 'Recepcionista',
        4 => 'Conserje',
        5 => 'Limpieza'
    ];

    return $roles[(int) $codigo_rol] ?? 'Personal';
}

function limpiar_nombre_creador_tarea($nombre, $rol) {
    // Corrección: Formato de nombre y rol ajustado a [Nombre] - [Rol], evitando duplicar el rol si viene pegado al nombre.
    $nombre_limpio = trim((string) $nombre);
    $rol_limpio = trim((string) $rol);

    if ($rol_limpio !== '') {
        $nombre_limpio = preg_replace('/\s+' . preg_quote($rol_limpio, '/') . '$/iu', '', $nombre_limpio);
    }

    return trim($nombre_limpio) !== '' ? trim($nombre_limpio) : 'Usuario';
}

function firma_actor_panel_tarea($id_usuario, $rol_usuario) {
    // Correccion definitiva: Misma firma emitida por cada panel para validar el autor real aunque la cookie de sesion se comparta entre pestañas.
    return hash_hmac('sha256', ((int) $id_usuario) . '|' . ((int) $rol_usuario), 'software_hotel_actor_panel_v1');
}

if (!isset($_SESSION['emp_auth']['id_usuario'])) {
    Logger::registrarLog('WARN', 'Intento no autorizado de guardar tarea sin sesión de empleado');
    http_response_code(401); // Transaccion: Codigo HTTP real para que el frontend no actualice la UI en errores.
    echo json_encode(["status" => "error", "mensaje" => "No estás autorizado"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    Logger::registrarLog('WARN', 'Intento de acceso por método no permitido al guardar tarea', [
        'id_usuario' => $idUsuarioLog,
        'metodo' => $_SERVER['REQUEST_METHOD']
    ]);
    http_response_code(405); // Transaccion: Codigo HTTP real para metodos invalidos.
    echo json_encode(["status" => "error", "mensaje" => "Método no permitido"]);
    exit();
}

$titulo = trim($_POST['titulo'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$id_sesion = (int) $_SESSION['emp_auth']['id_usuario'];
$id_creador_panel = isset($_POST['id_creador_panel']) ? (int) $_POST['id_creador_panel'] : 0;
$rol_creador_panel = isset($_POST['rol_creador_panel']) ? (int) $_POST['rol_creador_panel'] : 0;
$firma_creador_panel = trim($_POST['firma_creador_panel'] ?? '');
$panel_origen = trim($_POST['panel_origen'] ?? '');
// Solo se acepta el usuario del panel si su firma coincide; si no, se usa la sesion PHP activa.
$firma_panel_valida = $id_creador_panel > 0
    && $rol_creador_panel > 0
    && hash_equals(firma_actor_panel_tarea($id_creador_panel, $rol_creador_panel), $firma_creador_panel);
$id_creador_tarea = $firma_panel_valida ? $id_creador_panel : $id_sesion;
$estado = "Pendiente";
$fecha = date('Y-m-d H:i:s');

if ($titulo === '' || $categoria === '' || $descripcion === '') {
    Logger::registrarLog('WARN', 'Intento de guardar tarea con campos vacíos', [
        'id_usuario' => $idUsuarioLog
    ]);
    http_response_code(422); // Transaccion: Validacion de entrada antes de tocar la base de datos.
    echo json_encode(["status" => "error", "mensaje" => "Completa todos los campos de la tarea"]);
    exit();
}

try {
    $conexion->begin_transaction(); // Modificación: Rutina transaccional para crear tarea y mantener sincronizada la interfaz.

    // Corrección: Obtenemos nombre y rol reales del creador desde la BD para evitar inconsistencia con datos de sesión.
    $sql_usuario = "SELECT u.nom_usu, u.cod_rol_usu, r.des_rol FROM usuario u LEFT JOIN rol r ON u.cod_rol_usu = r.cod_rol WHERE u.id_usu = ?";
    $stmt_usuario = $conexion->prepare($sql_usuario);
    $stmt_usuario->bind_param("i", $id_creador_tarea);
    $stmt_usuario->execute();
    $resultado_usuario = $stmt_usuario->get_result();
    $datos_usuario = $resultado_usuario->fetch_assoc();
    $stmt_usuario->close();

    if (!$datos_usuario) {
        $conexion->rollback(); // Transaccion: No se crea la tarea si no se puede confirmar el autor real.
        
        Logger::registrarLog('WARN', 'Fallo al confirmar el creador de la tarea en base de datos', [
            'id_usuario_intento' => $id_creador_tarea
        ]);

        http_response_code(422);
        echo json_encode(["status" => "error", "mensaje" => "No se pudo confirmar el creador de la tarea"]);
        exit();
    }

    $nombre_creador_bd = $datos_usuario['nom_usu'] ?? 'Usuario';
    $cod_rol_bd = $datos_usuario['cod_rol_usu'] ?? null;
    $rol_nombre_bd = $datos_usuario['des_rol'] ?? obtener_nombre_rol_tarea($cod_rol_bd);

    if ($firma_panel_valida && (int) $cod_rol_bd !== $rol_creador_panel) {
        $conexion->rollback(); // Correccion definitiva: La firma no puede apuntar a un rol distinto al que existe en la BD.
        
        Logger::registrarLog('WARN', 'Inconsistencia de firma e identidad de rol al crear tarea', [
            'id_usuario' => $idUsuarioLog,
            'rol_bd' => $cod_rol_bd,
            'rol_panel' => $rol_creador_panel
        ]);

        http_response_code(409);
        echo json_encode(["status" => "error", "mensaje" => "La identidad del creador no coincide con la base de datos"]);
        exit();
    }

    $creador_nombre = limpiar_nombre_creador_tarea($nombre_creador_bd, $rol_nombre_bd);
    $rol_nombre = $rol_nombre_bd;

    $sql = "INSERT INTO tarea (tit_tar, cat_tar, des_tar, est_tar, fec_tar, cod_usu_tar) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssi", $titulo, $categoria, $descripcion, $estado, $fecha, $id_creador_tarea);
    $stmt->execute();

    $id_tarea = $stmt->insert_id;
    $stmt->close();
    $conexion->commit(); // Modificación: Rutina transaccional para crear tarea y mantener sincronizada la interfaz.

    // LOG DE ÉXITO: Tarea guardada correctamente con los datos del creador
    Logger::registrarLog('INFO', 'Nueva tarea creada exitosamente', [
        'id_usuario_operador' => $idUsuarioLog,
        'id_creador_tarea' => $id_creador_tarea,
        'cod_tar' => $id_tarea,
        'categoria' => $categoria
    ]);

    http_response_code(201); // Transaccion: Creacion confirmada; la UI solo renderiza si recibe 201/200 exitoso.
    echo json_encode([
        "status" => "exito",
        "mensaje" => "Tarea guardada correctamente en la Base de Datos",
        "id_tarea" => $id_tarea,
        "tarea" => [
            "id" => $id_tarea,
            "titulo" => $titulo,
            "categoria" => $categoria,
            "descripcion" => $descripcion,
            "estado" => $estado,
            "fecha" => $fecha,
            "creador" => $creador_nombre, // Corrección: Formato de nombre y rol ajustado a [Nombre] - [Rol].
            "creador_formateado" => $creador_nombre . " - " . $rol_nombre, // Correccion: Se entrega listo como [Nombre] - [Rol].
            "rol" => $cod_rol_bd,
            "rol_nombre" => $rol_nombre, // Corrección: Formato de nombre y rol ajustado a [Nombre] - [Rol].
            "panel_origen" => $panel_origen,
            "autor_validado_por" => $firma_panel_valida ? "firma_panel" : "sesion_php"
        ]
    ]);
} catch (Throwable $error) {
    try {
        $conexion->rollback(); // Modificación: Rutina transaccional para crear tarea y mantener sincronizada la interfaz.
    } catch (Throwable $rollback_error) {
        // Transaccion: Si no habia transaccion activa, conservamos una respuesta JSON controlada.
    }

    // LOG DE ERROR: Fallo transaccional o excepción en servidor
    Logger::registrarLog('ERROR', 'Excepción crítica al intentar guardar tarea', [
        'id_usuario' => $idUsuarioLog,
        'error_mensaje' => $error->getMessage()
    ]);

    http_response_code(500); // Transaccion: Error de servidor para impedir actualizaciones optimistas en la UI.
    echo json_encode(["status" => "error", "mensaje" => "Hubo un error al guardar la tarea"]);
}

$conexion->close();
?>