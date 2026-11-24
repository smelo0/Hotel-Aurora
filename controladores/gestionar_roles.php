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

// Cuentas que se agrupan bajo cada perfil de la tabla (el login trata 2, 4 y 5 como Empleado).
const GRUPOS_CUENTAS_ROL = [
    1 => [1],
    3 => [2, 3, 4, 5],
    6 => [6],
    7 => [7],
];

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
         WHERE r.cod_rol IN (1, 3, 6, 7)
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

    $conteos_cuentas = [];
    $resultado_cuentas = $conexion->query(
        'SELECT cod_rol_usu, COUNT(*) AS total, SUM(est_usu = 1) AS activas FROM usuario GROUP BY cod_rol_usu'
    );
    while ($fila_cuentas = $resultado_cuentas->fetch_assoc()) {
        $conteos_cuentas[(int) $fila_cuentas['cod_rol_usu']] = [
            'total' => (int) $fila_cuentas['total'],
            'activas' => (int) $fila_cuentas['activas'],
        ];
    }
    foreach (GRUPOS_CUENTAS_ROL as $grupo => $codigos_rol) {
        if (!isset($roles[$grupo])) {
            continue;
        }
        $roles[$grupo]['total_cuentas'] = 0;
        $roles[$grupo]['cuentas_activas'] = 0;
        foreach ($codigos_rol as $codigo_rol_usuario) {
            $roles[$grupo]['total_cuentas'] += $conteos_cuentas[$codigo_rol_usuario]['total'] ?? 0;
            $roles[$grupo]['cuentas_activas'] += $conteos_cuentas[$codigo_rol_usuario]['activas'] ?? 0;
        }
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

if ($metodo === 'GET' && $accion === 'cuentas') {
    $grupo = filter_var($_GET['cod_rol'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$grupo || !isset(GRUPOS_CUENTAS_ROL[$grupo])) {
        responder_roles(422, 'El rol indicado no es válido');
    }

    $codigos_rol = GRUPOS_CUENTAS_ROL[$grupo];
    $marcadores = implode(',', array_fill(0, count($codigos_rol), '?'));
    $stmt = $conexion->prepare(
        "SELECT u.id_usu, u.nom_usu, u.corr_usu, u.est_usu, u.cod_rol_usu, r.des_rol
         FROM usuario u
         LEFT JOIN rol r ON r.cod_rol = u.cod_rol_usu
         WHERE u.cod_rol_usu IN ($marcadores)
         ORDER BY (u.est_usu = 1) DESC, u.nom_usu ASC"
    );
    if (!$stmt) {
        Logger::registrarLog('ERROR', 'No se pudo preparar la consulta de cuentas de un rol', [
            'id_usuario' => $idUsuarioLog,
            'cod_rol' => $grupo,
            'error_db' => $conexion->error,
        ]);
        responder_roles(500, 'No se pudieron cargar las cuentas');
    }
    $stmt->bind_param(str_repeat('i', count($codigos_rol)), ...$codigos_rol);
    if (!$stmt->execute()) {
        Logger::registrarLog('ERROR', 'No se pudieron consultar las cuentas de un rol', [
            'id_usuario' => $idUsuarioLog,
            'cod_rol' => $grupo,
            'error_db' => $stmt->error,
        ]);
        $stmt->close();
        responder_roles(500, 'No se pudieron cargar las cuentas');
    }
    $resultado = $stmt->get_result();

    $cuentas = [];
    while ($fila = $resultado->fetch_assoc()) {
        $idCuenta = (int) $fila['id_usu'];
        $esActual = $idCuenta === (int) $idUsuarioLog;
        $cuentas[] = [
            'id_usu' => $idCuenta,
            'nombre' => (string) $fila['nom_usu'],
            'correo' => (string) ($fila['corr_usu'] ?? ''),
            'rol' => (string) ($fila['des_rol'] ?? ''),
            'activa' => (int) $fila['est_usu'] === 1,
            'es_actual' => $esActual,
            'editable' => $grupo !== 1 && !$esActual,
        ];
    }
    $stmt->close();

    responder_roles(200, 'Cuentas cargadas', [
        'cuentas' => $cuentas,
        'puede_gestionar' => usuario_tiene_permiso($conexion, 'roles.gestionar'),
    ]);
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

if ($accion === 'guardar_descripcion') {
    $codigoEntrada = $_POST['cod_rol'] ?? '';
    $descripcion = $_POST['detalle_rol'] ?? null;
    if (!is_string($codigoEntrada) || !is_string($descripcion)) {
        responder_roles(422, 'Los datos de la descripción no son válidos');
    }

    $codigo = filter_var($codigoEntrada, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $descripcion = trim($descripcion);
    if (
        !$codigo
        || !in_array($codigo, [1, 3, 6, 7], true)
        || mb_strlen($descripcion, 'UTF-8') > 200
    ) {
        responder_roles(422, 'La descripción debe tener máximo 200 caracteres y corresponder a un rol válido');
    }

    $stmt = $conexion->prepare('UPDATE rol SET detalle_rol = ? WHERE cod_rol = ?');
    if (!$stmt) {
        Logger::registrarLog('ERROR', 'No se pudo preparar la actualización de la descripción de un rol', [
            'id_usuario' => $idUsuarioLog,
            'cod_rol' => $codigo,
            'error_db' => $conexion->error,
        ]);
        responder_roles(500, 'No se pudo guardar la descripción');
    }
    $stmt->bind_param('si', $descripcion, $codigo);
    if (!$stmt->execute()) {
        Logger::registrarLog('ERROR', 'No se pudo actualizar la descripción de un rol', [
            'id_usuario' => $idUsuarioLog,
            'cod_rol' => $codigo,
            'error_db' => $stmt->error,
        ]);
        responder_roles(500, 'No se pudo guardar la descripción');
    }
    $stmt->close();
    Logger::registrarLog('INFO', 'Descripción de rol actualizada', [
        'id_usuario' => $idUsuarioLog,
        'cod_rol' => $codigo,
    ]);
    responder_roles(200, 'Descripción guardada correctamente', ['cod_rol' => $codigo]);
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
        || !in_array($codigo, [1, 3, 6, 7], true)
        || !is_array($permisos)
        || array_filter($permisos, static fn($permiso): bool => !is_string($permiso) || mb_strlen($permiso) > 60) !== []
    ) {
        Logger::registrarLog('WARN', 'Intento de guardar rol con formato de datos inválido', [
            'id_usuario' => $idUsuarioLog,
            'cod_rol' => $codigo
        ]);
        responder_roles(422, 'Solo se pueden editar los permisos de Gerente General, Empleado, Recepcionista y Cliente');
    }
    if ($codigo === 1) {
        Logger::registrarLog('WARN', 'Intento bloqueado de modificar los permisos del Gerente General', [
            'id_usuario' => $idUsuarioLog,
        ]);
        responder_roles(403, 'Los permisos del Gerente General están protegidos y no se pueden modificar');
    }

    $nombresRoles = [
        1 => 'Gerente General',
        3 => 'Empleado',
        6 => 'Cliente',
        7 => 'Recepcionista',
    ];
    $nombre = $nombresRoles[$codigo];

    if (!usuario_tiene_permiso($conexion, 'roles.asignar_permisos') && count($permisos) > 0) {
        Logger::registrarLog('WARN', 'Intento de asignar permisos sin autorización', [
            'id_usuario' => $idUsuarioLog
        ]);
        responder_roles(403, 'No tienes permiso para asignar permisos');
    }

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare('UPDATE rol SET des_rol = ? WHERE cod_rol = ?');
        $stmt->bind_param('si', $nombre, $codigo);
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

if ($accion === 'cambiar_estado_cuenta') {
    $idCuenta = filter_var($_POST['id_usu'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $estadoEntrada = $_POST['est_usu'] ?? null;
    if (!$idCuenta || !is_string($estadoEntrada) || !in_array($estadoEntrada, ['0', '1'], true)) {
        responder_roles(422, 'Los datos de la cuenta no son válidos');
    }
    $nuevoEstado = (int) $estadoEntrada;

    if ($idCuenta === (int) $idUsuarioLog) {
        Logger::registrarLog('WARN', 'Intento de cambiar el estado de la propia cuenta', [
            'id_usuario' => $idUsuarioLog,
        ]);
        responder_roles(403, 'No puedes cambiar el estado de tu propia cuenta');
    }

    $stmt = $conexion->prepare('SELECT cod_rol_usu FROM usuario WHERE id_usu = ? LIMIT 1');
    if (!$stmt) {
        responder_roles(500, 'No se pudo actualizar la cuenta');
    }
    $stmt->bind_param('i', $idCuenta);
    $stmt->execute();
    $stmt->bind_result($rolCuenta);
    $existe = $stmt->fetch();
    $stmt->close();

    if (!$existe) {
        responder_roles(404, 'La cuenta no existe');
    }
    if (!in_array((int) $rolCuenta, [2, 3, 4, 5, 6, 7], true)) {
        Logger::registrarLog('WARN', 'Intento bloqueado de cambiar el estado de una cuenta protegida', [
            'id_usuario' => $idUsuarioLog,
            'id_cuenta' => $idCuenta,
        ]);
        responder_roles(403, 'Las cuentas del Gerente General están protegidas');
    }

    $stmt = $conexion->prepare('UPDATE usuario SET est_usu = ? WHERE id_usu = ? AND cod_rol_usu IN (2, 3, 4, 5, 6, 7)');
    if (!$stmt) {
        responder_roles(500, 'No se pudo actualizar la cuenta');
    }
    $stmt->bind_param('ii', $nuevoEstado, $idCuenta);
    if (!$stmt->execute()) {
        Logger::registrarLog('ERROR', 'No se pudo cambiar el estado de una cuenta', [
            'id_usuario' => $idUsuarioLog,
            'id_cuenta' => $idCuenta,
            'error_db' => $stmt->error,
        ]);
        $stmt->close();
        responder_roles(500, 'No se pudo actualizar la cuenta');
    }
    $stmt->close();

    Logger::registrarLog('INFO', $nuevoEstado === 1 ? 'Cuenta habilitada' : 'Cuenta deshabilitada', [
        'id_usuario' => $idUsuarioLog,
        'id_cuenta' => $idCuenta,
        'cod_rol_cuenta' => (int) $rolCuenta,
    ]);
    responder_roles(200, $nuevoEstado === 1 ? 'Cuenta habilitada correctamente' : 'Cuenta deshabilitada correctamente', [
        'id_usu' => $idCuenta,
        'activa' => $nuevoEstado === 1,
    ]);
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