<?php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';

// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

header('Content-Type: application/json; charset=utf-8');

// Obtenemos el ID del usuario actual de la sesión para auditoría
$idUsuarioLog = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['user_auth']['id_usuario'] ?? 0;

function responder_roles($status, $mensaje, $datos = []) {
    http_response_code($status);
    echo json_encode(array_merge(['status' => $status < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!usuario_tiene_permiso($conexion, 'roles.ver')) {
    Logger::registrarLog('WARN', 'Intento no autorizado de consultar roles y permisos', [
        'id_usuario' => $idUsuarioLog
    ]);
    responder_roles(403, 'No tienes permiso para consultar los roles');
}

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'listar';

if ($metodo === 'GET' && $accion === 'listar') {
    $roles = [];
    $resultado_roles = $conexion->query(
        "SELECT r.cod_rol, r.des_rol, r.detalle_rol
         FROM rol r
         WHERE r.cod_rol IN (1, 3, 6)
         GROUP BY r.cod_rol, r.des_rol, r.detalle_rol ORDER BY r.cod_rol"
    );
    while ($rol = $resultado_roles->fetch_assoc()) {
        $rol['cod_rol'] = (int) $rol['cod_rol'];
        if ($rol['cod_rol'] === 3) {
            $rol['des_rol'] = 'Empleado';
        }
        $rol['permisos'] = [];
        $roles[$rol['cod_rol']] = $rol;
    }

    $permisos = [];
    $resultado_permisos = $conexion->query("SELECT cod_permiso, modulo, accion, des_permiso FROM permiso ORDER BY modulo, accion");
    while ($permiso = $resultado_permisos->fetch_assoc()) {
        $permisos[] = $permiso;
    }

    $resultado_asignaciones = $conexion->query("SELECT cod_rol, cod_permiso FROM rol_permiso");
    while ($asignacion = $resultado_asignaciones->fetch_assoc()) {
        $codigo_rol = (int) $asignacion['cod_rol'];
        if (isset($roles[$codigo_rol])) {
            $roles[$codigo_rol]['permisos'][] = $asignacion['cod_permiso'];
        }
    }
    if (isset($roles[1])) {
        $roles[1]['permisos'] = array_column($permisos, 'cod_permiso');
    }

    responder_roles(200, 'Roles cargados', ['roles' => array_values($roles), 'permisos' => $permisos, 'puede_gestionar' => usuario_tiene_permiso($conexion, 'roles.gestionar'), 'puede_asignar' => usuario_tiene_permiso($conexion, 'roles.asignar_permisos')]);
}

if ($metodo !== 'POST') {
    Logger::registrarLog('WARN', 'Intento de acceso por método no permitido al gestor de roles', [
        'id_usuario' => $idUsuarioLog,
        'metodo' => $metodo
    ]);
    responder_roles(405, 'Método no permitido');
}

exigir_csrf();

if (!usuario_tiene_permiso($conexion, 'roles.gestionar')) {
    Logger::registrarLog('WARN', 'Intento no autorizado de editar permisos de roles', [
        'id_usuario' => $idUsuarioLog
    ]);
    responder_roles(403, 'No tienes permiso para gestionar roles');
}

if ($accion === 'guardar') {
    $codigoEntrada = $_POST['cod_rol'] ?? '';
    $permisos = $_POST['permisos'] ?? [];

    if (!is_string($codigoEntrada)) {
        responder_roles(422, 'El identificador del rol no es válido');
    }
    $codigo = filter_var($codigoEntrada, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (
        !$codigo
        || !in_array($codigo, [1, 3, 6], true)
        || !is_array($permisos)
        || array_filter($permisos, static fn($permiso): bool => !is_string($permiso) || mb_strlen($permiso) > 60) !== []
    ) {
        Logger::registrarLog('WARN', 'Intento de guardar rol con formato de datos inválido', [
            'id_usuario' => $idUsuarioLog,
            'cod_rol' => $codigo
        ]);
        responder_roles(422, 'Solo se pueden editar los permisos de Gerente General, Empleado y Cliente');
    }

    $nombresRoles = [
        1 => 'Gerente General',
        3 => 'Empleado',
        6 => 'Cliente',
    ];
    $nombre = $nombresRoles[$codigo];
    $detalle = $codigo === 3 ? 'Perfil del personal del hotel.' : '';

    if (!usuario_tiene_permiso($conexion, 'roles.asignar_permisos') && count($permisos) > 0) {
        Logger::registrarLog('WARN', 'Intento de asignar permisos sin autorización', [
            'id_usuario' => $idUsuarioLog
        ]);
        responder_roles(403, 'No tienes permiso para asignar permisos');
    }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare('UPDATE rol SET des_rol = ?, detalle_rol = ? WHERE cod_rol = ?');
        $stmt->bind_param('ssi', $nombre, $detalle, $codigo);
        $stmt->execute();
        if ($stmt->affected_rows < 0) {
            throw new RuntimeException('No se pudo actualizar el rol');
        }

        if (usuario_tiene_permiso($conexion, 'roles.asignar_permisos')) {
            $stmt_borrar = $conexion->prepare('DELETE FROM rol_permiso WHERE cod_rol = ?');
            $stmt_borrar->bind_param('i', $codigo);
            $stmt_borrar->execute();

            $stmt_permiso = $conexion->prepare('INSERT INTO rol_permiso (cod_rol, cod_permiso) SELECT ?, cod_permiso FROM permiso WHERE cod_permiso = ?');
            foreach (array_unique($permisos) as $codigo_permiso) {
                if (!is_string($codigo_permiso) || mb_strlen($codigo_permiso) > 60) {
                    continue;
                }
                $stmt_permiso->bind_param('is', $codigo, $codigo_permiso);
                $stmt_permiso->execute();
            }
        }

        $conexion->commit();

        // LOG DE ÉXITO: Actualización de permisos de un perfil del sistema
        Logger::registrarLog('INFO', 'Rol y permisos actualizados con éxito', [
            'id_usuario' => $idUsuarioLog,
            'cod_rol' => (int) $codigo,
            'nombre_rol' => $nombre,
            'total_permisos_asignados' => count($permisos)
        ]);

        responder_roles(200, 'Rol guardado correctamente', ['cod_rol' => (int) $codigo]);
    } catch (Throwable $error) {
        $conexion->rollback();

        // LOG DE ERROR: Fallo transaccional al guardar rol
        Logger::registrarLog('ERROR', 'Fallo transaccional al intentar guardar un rol', [
            'id_usuario' => $idUsuarioLog,
            'cod_rol' => $codigo ?? null,
            'error_mensaje' => $error->getMessage()
        ]);

        responder_roles(500, 'No se pudo guardar el rol');
    }
}

if ($accion === 'eliminar') {
    $codigo = filter_var($_POST['cod_rol'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    Logger::registrarLog('WARN', 'Intento bloqueado de eliminar un rol del sistema', [
        'id_usuario' => $idUsuarioLog,
        'cod_rol' => $codigo
    ]);
    responder_roles(422, 'Los perfiles del sistema no se pueden eliminar');
}

Logger::registrarLog('WARN', 'Intento de ejecutar acción desconocida en el gestor de roles', [
    'id_usuario' => $idUsuarioLog,
    'accion' => $accion
]);

responder_roles(400, 'Acción no válida');