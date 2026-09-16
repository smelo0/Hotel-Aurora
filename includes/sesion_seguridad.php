<?php
declare(strict_types=1);

/**
 * sesion_seguridad.php
 * Debe incluirse SIEMPRE al inicio absoluto de cada vista/controlador protegido,
 * ANTES de cualquier otro session_start() y antes de imprimir HTML.
 *
 * Responsabilidades:
 *  1. Configurar la cookie de sesión con HttpOnly + SameSite (Tridente Defensivo).
 *  2. Hard Lock: destruir la sesión si pasan más de 5 minutos de inactividad real.
 */

// --- 1. Cookies seguras (debe ir ANTES de session_start) ---
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,   // cambiar a true en producción con HTTPS
        'httponly' => true,    // evita acceso vía JS (XSS)
        'samesite' => 'Lax',   // previene CSRF
    ]);
    session_start();
}

// --- 2. Hard Lock: 5 minutos de inactividad real ---
define('HARD_LOCK_SECONDS', 300);

$haySesionActiva = !empty($_SESSION['user_auth']) || !empty($_SESSION['emp_auth']);

if ($haySesionActiva) {
    if (isset($_SESSION['ultimo_acceso'])) {
        $tiempoInactivo = time() - $_SESSION['ultimo_acceso'];

        if ($tiempoInactivo > HARD_LOCK_SECONDS) {
            $_SESSION = [];

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_destroy();
            header('Location: /Hotel-Aurora/controladores/logout.php?panel=user&error=timeout');
            exit;
        }
    }

    // Actualiza la marca de tiempo en cada carga de página protegida
    $_SESSION['ultimo_acceso'] = time();
}