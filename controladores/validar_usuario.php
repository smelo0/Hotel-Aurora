<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

require_once __DIR__ . '/../includes/sesion_seguridad.php';

require_once '../configuracion/conexion.php';

function validar_recaptcha(string $token): bool
{
    $secretKey = trim((string) (getenv('RECAPTCHA_SECRET_KEY') ?: ($_ENV['RECAPTCHA_SECRET_KEY'] ?? '')));
    $token = trim($token);

    if ($secretKey === '' || $token === '') {
        return false;
    }

    $payload = http_build_query([
        'secret' => $secretKey,
        'response' => $token,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($payload),
            'content' => $payload,
            'ignore_errors' => true,
            'timeout' => 10,
        ],
    ]);

    $respuesta = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
    if ($respuesta === false) {
        return false;
    }

    $datos = json_decode($respuesta, true);
    return is_array($datos) && ($datos['success'] ?? false) === true;
}

function redirigir_login(string $query = ''): never
{
    $destino = '../interfaz/loggins/index_usu.php';
    if ($query !== '') {
        $destino .= '?' . $query;
    }

    header('Location: ' . $destino);
    exit();
}

if (!isset($conexion) || $conexion->connect_errno) {
    redirigir_login('error=conexion_fallida');
}

$conexion->set_charset('utf8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir_login();
}

$correo = trim((string) ($_POST['correo'] ?? ''));
$passwordIngresada = (string) ($_POST['password'] ?? '');
$captchaToken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
$captchaMarcado = (string) ($_POST['captcha'] ?? '');

if ($correo === '' || $passwordIngresada === '') {
    redirigir_login('error=vacio');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    redirigir_login('error=email');
}

if ($captchaMarcado !== 'on' && $captchaToken === '') {
    redirigir_login('error=captcha');
}

if ($captchaToken !== '' && !validar_recaptcha($captchaToken)) {
    redirigir_login('error=captcha');
}

$sql = 'SELECT id_usu, nom_usu, cod_rol_usu, psw_usu FROM usuario WHERE corr_usu = ? LIMIT 1';
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $conexion->close();
    redirigir_login('error=bd_preparacion');
}

$stmt->bind_param('s', $correo);

if (!$stmt->execute()) {
    $stmt->close();
    $conexion->close();
    redirigir_login('error=bd_ejecucion');
}

$stmt->store_result();

if ($stmt->num_rows !== 1) {
    $stmt->close();
    $conexion->close();
    redirigir_login('error=credenciales');
}
     
$stmt->bind_result($idUsuario, $nombreUsuario, $rolUsuario, $hashAlmacenado);

if (!$stmt->fetch()) {
    $stmt->close();
    $conexion->close();
    redirigir_login('error=bd_ejecucion');
}

if (!$hashAlmacenado || !password_verify($passwordIngresada, $hashAlmacenado)) {
    $stmt->close();
    $conexion->close();
    redirigir_login('error=credenciales');
}

$rolActual = (int) $rolUsuario;
$stmt->close();
$conexion->close();

session_regenerate_id(true);
unset($_SESSION['emp_auth'], $_SESSION['user_auth']);

switch ($rolActual) {
    case 1:
    case 2:
        $_SESSION['emp_auth'] = [
            'id_usuario' => (int) $idUsuario,
            'nombre_usuario' => (string) $nombreUsuario,
            'rol_usuario' => $rolActual,
        ];
        header('Location: ../interfaz/admin/index_ad.php');
        exit();

    case 3:
    case 4:
    case 5:
        $_SESSION['emp_auth'] = [
            'id_usuario' => (int) $idUsuario,
            'nombre_usuario' => (string) $nombreUsuario,
            'rol_usuario' => $rolActual,
        ];
        header('Location: ../interfaz/empleado/index.php');
        exit();

    case 6:
        $_SESSION['user_auth'] = [
            'id_usuario' => (int) $idUsuario,
            'nombre_usuario' => (string) $nombreUsuario,
            'rol_usuario' => $rolActual,
        ];
        Logger::registrarLog('INFO', 'El usuario ha iniciado sesion', [
            'id_usuario' => $_SESSION['user_auth']['id_usuario'],
        ]);
        header('Location: ../interfaz_usu.php');
        exit();

    default:
        redirigir_login('error=rol');
}
