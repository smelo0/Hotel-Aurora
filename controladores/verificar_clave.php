<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

define('SKIP_HARD_LOCK_CHECK', true);
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';

$clave = $_POST['clave'] ?? '';
$idUsuario = (int) ($_SESSION['user_auth']['id_usuario'] ?? $_SESSION['emp_auth']['id_usuario'] ?? 0);

if ($clave === '' || $idUsuario === 0) {
    echo json_encode(['valido' => false]);
    exit;
}

$stmt = $conexion->prepare("SELECT psw_usu FROM usuario WHERE id_usu = ?");
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$stmt->bind_result($hashGuardado);
$stmt->fetch();
$stmt->close();

$valido = $hashGuardado && password_verify($clave, $hashGuardado);

if ($valido) {
    $_SESSION['ultimo_acceso'] = time();
}

echo json_encode(['valido' => $valido]);