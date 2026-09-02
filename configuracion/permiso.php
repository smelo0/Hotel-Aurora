<?php
function usuario_tiene_permiso(mysqli $conexion, string $codigo_permiso, ?int $codigo_rol = null): bool {
    $codigo_rol = $codigo_rol ?? (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);
    if ($codigo_rol < 1) {
        return false;
    }

    $stmt = $conexion->prepare(
        'SELECT 1 FROM rol_permiso rp INNER JOIN permiso p ON p.cod_permiso = rp.cod_permiso WHERE rp.cod_rol = ? AND p.cod_permiso = ? LIMIT 1'
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('is', $codigo_rol, $codigo_permiso);
    $stmt->execute();
    $stmt->store_result();
    $tiene_permiso = $stmt->num_rows === 1;
    $stmt->close();
    return $tiene_permiso;
}

function exigir_permiso(mysqli $conexion, string $codigo_permiso): void {
    if (usuario_tiene_permiso($conexion, $codigo_permiso)) {
        return;
    }

    $acepta_json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    if ($acepta_json) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'mensaje' => 'No tienes permisos para realizar esta acción'], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(403);
        echo 'Acceso denegado';
    }
    exit;
}
?>
