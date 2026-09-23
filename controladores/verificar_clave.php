<?php

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

define('SKIP_HARD_LOCK_CHECK', true);
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../configuracion/conexion.php';
// 1. Asegúrate de incluir el autoloader de Composer si no está en 'sesion_seguridad.php'
require_once __DIR__ . '/../vendor/autoload.php'; 

use App\Logger; // Importas tu clase Logger

$clave = $_POST['clave'] ?? '';
$idUsuario = (int) ($_SESSION['user_auth']['id_usuario'] ?? $_SESSION['emp_auth']['id_usuario'] ?? 0);

if ($clave === '' || $idUsuario === 0) {
    // Opcional: Registrar si intentan verificar clave sin sesión o vacía
    Logger::registrarLog('WARN', 'Intento de verificación de clave con datos incompletos', [
        'id_usuario' => $idUsuario
    ]);
    
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
    
    // 2. LOG DE ÉXITO: El usuario confirmó su clave correctamente (ej. para acciones sensibles)
    Logger::registrarLog('INFO', 'Verificación de clave exitosa', [
        'id_usuario' => $idUsuario
    ]);
} else {
    // 3. LOG DE ADVERTENCIA: La clave ingresada fue incorrecta (¡Muy importante para seguridad!)
    Logger::registrarLog('WARN', 'Intento fallido de verificación de clave', [
        'id_usuario' => $idUsuario
    ]);
}

echo json_encode(['valido' => $valido]);