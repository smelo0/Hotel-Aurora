<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/sesion_seguridad.php';

// Si ya existe una sesión activa, redirigir a su panel correspondiente
if (isset($_SESSION['emp_auth']['rol_usuario'])) {
    $rol = (int) $_SESSION['emp_auth']['rol_usuario'];
    if (in_array($rol, [1, 2], true)) {
        header('Location: interfaz/admin/index_ad.php');
        exit();
    }
    if (in_array($rol, [3, 4, 5], true)) {
        header('Location: interfaz/empleado/index.php');
        exit();
    }
}

if (isset($_SESSION['user_auth']['rol_usuario']) && (int) $_SESSION['user_auth']['rol_usuario'] === 6) {
    header('Location: interfaz/usuario/index_usua.php');
    exit();
}

// Por defecto, redirigir a la pagina principal de reservas del hotel
header('Location: interfaz_usu.php');
exit();
