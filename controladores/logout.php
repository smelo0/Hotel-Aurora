<?php
// 1. Iniciar la sesión

require_once __DIR__ . '/../includes/sesion_seguridad.php';

// Incluir tu clase Logger (ajusta la ruta según la ubicación real de tu archivo Logger.php)
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;


// Capturar datos del usuario ANTES de destruir la sesión
$id_usuario = $_SESSION['user_auth']['id_usuario'] ?? $_SESSION['emp_auth']['id_usuario'] ?? 'Desconocido';
$rol_usuario = $_SESSION['user_auth']['rol_usuario'] ?? $_SESSION['emp_auth']['rol_usuario'] ?? 'Desconocido';

try {
    Logger::registrarLog('INFO', 'Cierre de sesión manual', [
        'id_usuario'  => $id_usuario,
        'rol_usuario' => $rol_usuario,
    ]);
} catch (\Throwable $e) {
    // Si hay un error con la clase o permisos, lo guardará en el error_log de Apache/PHP
    error_log("ERROR EN LOGOUT: " . $e->getMessage());
}

// 2. Limpiar todas las variables de sesión
$_SESSION = array();

// 3. Si se desea destruir la cookie de sesión completamente
if (ini_get("session.use_cookies")) {
    // Registrar el log de cierre de sesión
    
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión
session_destroy();

// 5. Redireccionar directamente a la interfaz pública/usuario
header("Location:../interfaz_usu.php");
exit();
?>