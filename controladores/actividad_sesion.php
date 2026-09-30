<?php
declare(strict_types=1);

define('ALLOW_SESSION_ACTIVITY_HANDLER', true);
require_once __DIR__ . '/../includes/sesion_seguridad.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'mensaje' => 'Método no permitido']);
    exit;
}

exigir_csrf();

$haySesionActiva = !empty($_SESSION['user_auth']) || !empty($_SESSION['emp_auth']);
if (!$haySesionActiva) {
    http_response_code(401);
    echo json_encode(['status' => 'unauthenticated']);
    exit;
}

$accion = (string) ($_POST['accion'] ?? '');
$ahora = time();
$bloqueada = !empty($_SESSION['sesion_bloqueada']);

if ($bloqueada) {
    $bloqueadaEn = (int) ($_SESSION['sesion_bloqueada_en'] ?? $ahora);
    $pausaConcedida = !empty($_SESSION['sesion_bloqueo_pausa_concedida']);
    $venceEn = $bloqueadaEn + SESSION_LOCK_COUNTDOWN_SECONDS
        + ($pausaConcedida ? SESSION_UNLOCK_PAUSE_SECONDS : 0);

    if ($ahora >= $venceEn) {
        expirar_sesion_por_inactividad(true);
    }

    http_response_code(423);
    echo json_encode([
        'status' => 'locked',
        'bloqueada' => true,
        'bloqueada_en' => $bloqueadaEn,
        'segundos_restantes' => max(0, $venceEn - $ahora),
    ]);
    exit;
}

$ultimaActividad = (int) ($_SESSION['ultimo_acceso'] ?? $ahora);
$inactivoPor = $ahora - $ultimaActividad;

if ($accion === 'estado') {
    echo json_encode(['status' => 'active', 'bloqueada' => false]);
    exit;
}

if ($accion === 'actividad') {
    if ($inactivoPor >= SESSION_IDLE_LIMIT_SECONDS) {
        $_SESSION['sesion_bloqueada'] = true;
        $_SESSION['sesion_bloqueada_en'] = $ultimaActividad + SESSION_IDLE_LIMIT_SECONDS;
        unset($_SESSION['sesion_bloqueo_pausa_concedida'], $_SESSION['sesion_bloqueo_intentos']);
        http_response_code(423);
        echo json_encode([
            'status' => 'locked',
            'bloqueada' => true,
            'bloqueada_en' => (int) $_SESSION['sesion_bloqueada_en'],
            'segundos_restantes' => max(0, SESSION_LOCK_COUNTDOWN_SECONDS - ($ahora - (int) $_SESSION['sesion_bloqueada_en'])),
        ]);
        exit;
    }

    $_SESSION['ultimo_acceso'] = $ahora;
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($accion === 'bloquear') {
    if ($inactivoPor < SESSION_IDLE_LIMIT_SECONDS) {
        http_response_code(409);
        echo json_encode(['status' => 'not_due', 'reintentar_en' => SESSION_IDLE_LIMIT_SECONDS - $inactivoPor]);
        exit;
    }

    $_SESSION['sesion_bloqueada'] = true;
    $_SESSION['sesion_bloqueada_en'] = $ultimaActividad + SESSION_IDLE_LIMIT_SECONDS;
    unset($_SESSION['sesion_bloqueo_pausa_concedida'], $_SESSION['sesion_bloqueo_intentos']);
    $segundosRestantes = max(0, (int) $_SESSION['sesion_bloqueada_en'] + SESSION_LOCK_COUNTDOWN_SECONDS - $ahora);

    http_response_code(423);
    echo json_encode([
        'status' => 'locked',
        'bloqueada' => true,
        'bloqueada_en' => (int) $_SESSION['sesion_bloqueada_en'],
        'segundos_restantes' => $segundosRestantes,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'mensaje' => 'Acción no válida']);