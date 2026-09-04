<?php
// 1. Iniciar la sesión para poder acceder a ella
session_start();

// 2. Limpiar todas las variables de sesión
$_SESSION = array();

// 3. Si se desea destruir la cookie de sesión completamente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión
session_destroy();

// 5. Redireccionar directamente a la interfaz pública/usuario
header("Location: ../interfaz_usu.php");
exit(); // Es primordial usar exit() para detener la ejecución inmediatamente
?>