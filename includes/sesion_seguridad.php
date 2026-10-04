<?php
declare(strict_types=1);

/**
 * sesion_seguridad.php
 * Debe incluirse SIEMPRE al inicio absoluto de cada vista/controlador protegido,
 * ANTES de cualquier otro session_start() y antes de imprimir HTML.
 *
 * Responsabilidades:
 *  1. Configurar la cookie de sesión con HttpOnly + SameSite.
 *  2. Impedir que el navegador almacene respuestas de las interfaces.
 *  3. Aplicar el bloqueo de inactividad en el servidor.
 */

// 1. Incluimos el Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

// --- 1. Cookies seguras (debe ir ANTES de session_start) ---
if (session_status() === PHP_SESSION_NONE) {
    $requestIsHttps = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $secureCookieConfigured = strtolower((string) (
        getenv('SESSION_COOKIE_SECURE')
        ?: ($_ENV['SESSION_COOKIE_SECURE'] ?? $_SERVER['SESSION_COOKIE_SECURE'] ?? '')
    )) === 'true';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $requestIsHttps || $secureCookieConfigured,
        'httponly' => true,    // evita acceso vía JS (XSS)
        'samesite' => 'Lax',   // previene CSRF
    ]);
    session_start();

    if ($requestIsHttps || $secureCookieConfigured) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// --- 2. Bloqueo de inactividad del lado del servidor ---
define('SESSION_IDLE_LIMIT_SECONDS', 150);
define('SESSION_LOCK_COUNTDOWN_SECONDS', 60);
define('SESSION_UNLOCK_PAUSE_SECONDS', 15);

if (!function_exists('expirar_sesion_por_inactividad')) {
    function expirar_sesion_por_inactividad(bool $respuestaJson = false): void
    {
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

        if ($respuestaJson) {
            http_response_code(440);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'expired', 'redirect' => '/Hotel-Aurora/interfaz_usu.php']);
            exit;
        }

        header('Location: /Hotel-Aurora/interfaz_usu.php?error=inactividad');
        exit;
    }
}

if (!function_exists('responder_sesion_bloqueada')) {
    function responder_sesion_bloqueada(): void
    {
        $bloqueadaEn = (int) ($_SESSION['sesion_bloqueada_en'] ?? time());
        $pausaConcedida = !empty($_SESSION['sesion_bloqueo_pausa_concedida']);
        $venceEn = $bloqueadaEn + SESSION_LOCK_COUNTDOWN_SECONDS
            + ($pausaConcedida ? SESSION_UNLOCK_PAUSE_SECONDS : 0);

        if (time() >= $venceEn) {
            expirar_sesion_por_inactividad(true);
        }

        http_response_code(423);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'locked',
            'bloqueada' => true,
            'bloqueada_en' => $bloqueadaEn,
            'segundos_restantes' => max(0, $venceEn - time()),
        ]);
        exit;
    }
}

$haySesionActiva = !empty($_SESSION['user_auth']) || !empty($_SESSION['emp_auth']);

if ($haySesionActiva) {
    if (!isset($_SESSION['ultimo_acceso'])) {
        $_SESSION['ultimo_acceso'] = time();
    }

    if (empty($_SESSION['sesion_bloqueada'])) {
        $tiempoInactivo = time() - (int) $_SESSION['ultimo_acceso'];

        if ($tiempoInactivo >= SESSION_IDLE_LIMIT_SECONDS) {
            $_SESSION['sesion_bloqueada'] = true;
            $_SESSION['sesion_bloqueada_en'] = (int) $_SESSION['ultimo_acceso'] + SESSION_IDLE_LIMIT_SECONDS;
            unset($_SESSION['sesion_bloqueo_pausa_concedida'], $_SESSION['sesion_bloqueo_intentos']);

            $idUsuarioInactivo = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['user_auth']['id_usuario'] ?? 'desconocido';
            Logger::registrarLog('INFO', 'Sesión bloqueada por inactividad', [
                'usuario_id' => $idUsuarioInactivo,
                'tiempo_inactivo' => $tiempoInactivo,
            ]);
        }
    }

    $scriptActual = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $esVerificacionDesbloqueo = defined('ALLOW_SESSION_UNLOCK') && ALLOW_SESSION_UNLOCK;
    $esGestorActividad = defined('ALLOW_SESSION_ACTIVITY_HANDLER') && ALLOW_SESSION_ACTIVITY_HANDLER;
    $esCierreSesion = basename($scriptActual) === 'logout.php';

    if (!empty($_SESSION['sesion_bloqueada']) && !$esVerificacionDesbloqueo && !$esGestorActividad && !$esCierreSesion) {
        $esApi = preg_match('~/(controladores|includes)/~i', $scriptActual) === 1;
        if ($esApi) {
            responder_sesion_bloqueada();
        }

        expirar_sesion_por_inactividad(false);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('exigir_csrf')) {
    function exigir_csrf(): void
    {
        $token = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $esperado = (string) ($_SESSION['csrf_token'] ?? '');

        if ($esperado === '' || $token === '' || !hash_equals($esperado, $token)) {
            // Log del posible intento de ataque CSRF
            $idUsuarioCsrf = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['user_auth']['id_usuario'] ?? 'desconocido';
            Logger::registrarLog('WARNING', 'Intento de solicitud bloqueado por fallo de validación CSRF', ['usuario_id' => $idUsuarioCsrf, 'ruta' => $_SERVER['REQUEST_URI'] ?? '']);

            http_response_code(419);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'mensaje' => 'Solicitud no válida']);
            exit;
        }
    }
}