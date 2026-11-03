<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

define('ALLOW_SESSION_UNLOCK', true);
require_once __DIR__ . '/../includes/sesion_seguridad.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'valido' => false]);
    exit;
}

// En un mismo navegador pueden coexistir una sesión de cliente (user_auth) y una de empleado/administrador
// (emp_auth), y la cuenta de la sesión puede cambiar si se inicia sesión con otra en otra pestaña.
// La página envía el id de la cuenta a la que pertenece ("uid"): la contraseña se compara con esa cuenta y,
// si la sesión ya pertenece a otra, se avisa en lugar de rechazar la clave como "incorrecta".
$idEmpleado = (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0);
$idCliente = (int) ($_SESSION['user_auth']['id_usuario'] ?? 0);
$uidPagina = (int) ($_POST['uid'] ?? 0);
$rutaOrigen = (string) parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH);
if ($uidPagina > 0) {
    if ($uidPagina === $idEmpleado) {
        $idsCandidatos = [$idEmpleado];
    } elseif ($uidPagina === $idCliente) {
        $idsCandidatos = [$idCliente];
    } else {
        http_response_code(409);
        echo json_encode(['status' => 'sesion_cambiada', 'valido' => false]);
        exit;
    }
} elseif ($idEmpleado > 0 && preg_match('~/interfaz/(admin|empleado)/~i', $rutaOrigen) === 1) {
    $idsCandidatos = [$idEmpleado];
} elseif ($idCliente > 0 && preg_match('~/(interfaz_usu\.php|interfaz/usuario/)~i', $rutaOrigen) === 1) {
    $idsCandidatos = [$idCliente];
} else {
    // Sin dato de la página: se acepta la clave de cualquiera de las cuentas abiertas en esta sesión.
    $idsCandidatos = array_values(array_unique(array_filter([$idEmpleado, $idCliente])));
}

$tokenCsrf = (string) ($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
$tokenCsrfActual = (string) ($_SESSION['csrf_token'] ?? '');
if ($tokenCsrfActual === '' || $tokenCsrf === '' || !hash_equals($tokenCsrfActual, $tokenCsrf)) {
    $origen = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $urlOrigen = $origen !== '' ? $origen : $referer;
    $partesOrigen = parse_url($urlOrigen);
    $hostSolicitud = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $partesHostSolicitud = parse_url('//' . $hostSolicitud);
    $esMismoOrigen = is_array($partesOrigen)
        && is_array($partesHostSolicitud)
        && strtolower((string) ($partesOrigen['host'] ?? '')) === strtolower((string) ($partesHostSolicitud['host'] ?? ''))
        && (int) ($partesOrigen['port'] ?? 0) === (int) ($partesHostSolicitud['port'] ?? 0)
        && strtolower((string) ($partesOrigen['scheme'] ?? '')) === (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off' ? 'https' : 'http')
        && (string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        && $idsCandidatos !== [];

    if ($esMismoOrigen) {
        http_response_code(419);
        echo json_encode([
            'status' => 'csrf_refresh',
            'csrf_token' => csrf_token(),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    exigir_csrf();
}

$idUsuario = $idsCandidatos[0] ?? 0;
if ($idUsuario === 0) {
    http_response_code(401);
    echo json_encode(['status' => 'sesion_cambiada', 'valido' => false]);
    exit;
}

$bloqueadaEn = (int) ($_SESSION['sesion_bloqueada_en'] ?? 0);
if (empty($_SESSION['sesion_bloqueada']) || $bloqueadaEn === 0) {
    // La sesión ya no está bloqueada (por ejemplo se desbloqueó desde otra pestaña): el aviso debe cerrarse.
    echo json_encode(['status' => 'active', 'valido' => false]);
    exit;
}

$ahora = time();
$pausaConcedida = !empty($_SESSION['sesion_bloqueo_pausa_concedida']);
$venceEn = $bloqueadaEn + SESSION_LOCK_COUNTDOWN_SECONDS
    + ($pausaConcedida ? SESSION_UNLOCK_PAUSE_SECONDS : 0);
if ($ahora >= $venceEn) {
    expirar_sesion_por_inactividad(true);
}

if (($_POST['accion'] ?? '') === 'pausar') {
    if ($pausaConcedida || $ahora >= $bloqueadaEn + SESSION_LOCK_COUNTDOWN_SECONDS) {
        http_response_code(409);
        echo json_encode(['status' => 'pause_unavailable']);
        exit;
    }

    $_SESSION['sesion_bloqueo_pausa_concedida'] = true;
    $_SESSION['sesion_bloqueo_pausa_hasta'] = min(
        $ahora + SESSION_UNLOCK_PAUSE_SECONDS,
        $bloqueadaEn + SESSION_LOCK_COUNTDOWN_SECONDS + SESSION_UNLOCK_PAUSE_SECONDS
    );
    echo json_encode([
        'status' => 'paused',
        'segundos_pausa' => max(0, (int) $_SESSION['sesion_bloqueo_pausa_hasta'] - $ahora),
        'segundos_restantes' => max(0, $venceEn - $ahora),
    ]);
    exit;
}

require_once __DIR__ . '/../configuracion/conexion.php';

$clave = (string) ($_POST['clave'] ?? '');
if ($clave === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'valido' => false]);
    exit;
}

if ((int) ($_SESSION['sesion_bloqueo_intentos'] ?? 0) >= 5) {
    http_response_code(429);
    echo json_encode(['status' => 'error', 'valido' => false, 'intentos_agotados' => true]);
    exit;
}

$valido = false;
try {
    $stmt = $conexion->prepare('SELECT psw_usu FROM usuario WHERE id_usu = ? LIMIT 1');
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la verificación de contraseña.');
    }

    foreach ($idsCandidatos as $idCandidato) {
        $stmt->bind_param('i', $idCandidato);
        $stmt->execute();
        $resultado = $stmt->get_result()->fetch_assoc();
        $hashGuardado = (string) ($resultado['psw_usu'] ?? '');
        if ($hashGuardado !== '' && password_verify($clave, $hashGuardado)) {
            $valido = true;
            break;
        }
    }
    $stmt->close();
} catch (Throwable $error) {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    error_log('Error al verificar contraseña para desbloquear sesión: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'valido' => false,
        'mensaje' => 'No fue posible verificar la contraseña. Intenta de nuevo.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($valido) {
    unset(
        $_SESSION['sesion_bloqueada'],
        $_SESSION['sesion_bloqueada_en'],
        $_SESSION['sesion_bloqueo_pausa_concedida'],
        $_SESSION['sesion_bloqueo_pausa_hasta'],
        $_SESSION['sesion_bloqueo_intentos']
    );
    $_SESSION['ultimo_acceso'] = time();
    session_regenerate_id(true);
} else {
    $_SESSION['sesion_bloqueo_intentos'] = (int) ($_SESSION['sesion_bloqueo_intentos'] ?? 0) + 1;
}

echo json_encode([
    'status' => $valido ? 'ok' : 'invalid_password',
    'valido' => $valido,
    'intentos_restantes' => max(0, 5 - (int) ($_SESSION['sesion_bloqueo_intentos'] ?? 0)),
]);