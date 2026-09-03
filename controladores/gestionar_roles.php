<?php
session_start();
require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../configuracion/permiso.php';

header('Content-Type: application/json; charset=utf-8');

function responder_roles($status, $mensaje, $datos = []) {
    http_response_code($status);
    echo json_encode(array_merge(['status' => $status < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!usuario_tiene_permiso($conexion, 'roles.ver')) {
    responder_roles(403, 'No tienes permiso para consultar los roles');
}

$metodo = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? $_POST['accion'] ?? 'listar';

if ($metodo === 'GET' && $accion === 'listar') {
    $roles = [];
    $resultado_roles = $conexion->query(
        "SELECT r.cod_rol, r.des_rol, r.detalle_rol, COUNT(u.id_usu) AS usuarios
         FROM rol r LEFT JOIN usuario u ON u.cod_rol_usu = r.cod_rol
         GROUP BY r.cod_rol, r.des_rol, r.detalle_rol ORDER BY r.cod_rol"
    );
    while ($rol = $resultado_roles->fetch_assoc()) {
        $rol['cod_rol'] = (int) $rol['cod_rol'];
        $rol['usuarios'] = (int) $rol['usuarios'];
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

    responder_roles(200, 'Roles cargados', ['roles' => array_values($roles), 'permisos' => $permisos, 'puede_gestionar' => usuario_tiene_permiso($conexion, 'roles.gestionar'), 'puede_asignar' => usuario_tiene_permiso($conexion, 'roles.asignar_permisos')]);
}

if ($metodo !== 'POST') {
    responder_roles(405, 'Método no permitido');
}

if (!usuario_tiene_permiso($conexion, 'roles.gestionar')) {
    responder_roles(403, 'No tienes permiso para gestionar roles');
}

if ($accion === 'guardar') {
    $codigo = filter_var($_POST['cod_rol'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $nombre = trim($_POST['des_rol'] ?? '');
    $detalle = trim($_POST['detalle_rol'] ?? '');
    $permisos = $_POST['permisos'] ?? [];

    if ($nombre === '' || mb_strlen($nombre) > 200 || mb_strlen($detalle) > 200 || !is_array($permisos)) {
        responder_roles(422, 'Completa un nombre válido y una descripción de máximo 200 caracteres');
    }

    if (!usuario_tiene_permiso($conexion, 'roles.asignar_permisos') && count($permisos) > 0) {
        responder_roles(403, 'No tienes permiso para asignar permisos');
    }

    $conexion->begin_transaction();
    try {
        if ($codigo) {
            $stmt = $conexion->prepare('UPDATE rol SET des_rol = ?, detalle_rol = ? WHERE cod_rol = ?');
            $stmt->bind_param('ssi', $nombre, $detalle, $codigo);
            $stmt->execute();
            if ($stmt->affected_rows < 0) {
                throw new RuntimeException('No se pudo actualizar el rol');
            }
        } else {
            $stmt = $conexion->prepare('INSERT INTO rol (des_rol, detalle_rol) VALUES (?, ?)');
            $stmt->bind_param('ss', $nombre, $detalle);
            $stmt->execute();
            $codigo = $stmt->insert_id;
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
        responder_roles(200, 'Rol guardado correctamente', ['cod_rol' => (int) $codigo]);
    } catch (Throwable $error) {
        $conexion->rollback();
        responder_roles(500, 'No se pudo guardar el rol');
    }
}

if ($accion === 'eliminar') {
    $codigo = filter_var($_POST['cod_rol'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$codigo || $codigo <= 6) {
        responder_roles(422, 'Los roles del sistema no se pueden eliminar');
    }

    $usuarios = 0;
    $stmt_usuarios = $conexion->prepare('SELECT COUNT(*) FROM usuario WHERE cod_rol_usu = ?');
    $stmt_usuarios->bind_param('i', $codigo);
    $stmt_usuarios->execute();
    $stmt_usuarios->bind_result($usuarios);
    $stmt_usuarios->fetch();
    $stmt_usuarios->close();

    if ($usuarios > 0) {
        responder_roles(409, 'No se puede eliminar un rol que tiene usuarios asignados');
    }

    $stmt = $conexion->prepare('DELETE FROM rol WHERE cod_rol = ?');
    $stmt->bind_param('i', $codigo);
    $stmt->execute();
    responder_roles($stmt->affected_rows === 1 ? 200 : 404, $stmt->affected_rows === 1 ? 'Rol eliminado correctamente' : 'Rol no encontrado');
}

responder_roles(400, 'Acción no válida');
