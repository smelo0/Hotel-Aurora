<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;
use App\Recaptcha;

require_once __DIR__ . '/../includes/sesion_seguridad.php';

require_once __DIR__ . '/../configuracion/conexion.php';

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

exigir_csrf();

$correo = trim((string) ($_POST['correo'] ?? ''));
$passwordIngresada = (string) ($_POST['password'] ?? '');
$captchaToken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));

if ($correo === '' || $passwordIngresada === '') {
    redirigir_login('error=vacio');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    redirigir_login('error=email');
}

if (Recaptcha::isEnabled() && !Recaptcha::verify($captchaToken)) {
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
        Logger::registrarLog('INFO', 'El usuario ha iniciado sesion como administrador', [
            'id_usuario' => $_SESSION['emp_auth']['id_usuario'],
            'rol_usuario' => $_SESSION['emp_auth']['rol_usuario']
        ]);
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
        Logger::registrarLog('INFO', 'El usuario ha iniciado sesion como empleado', [
            'id_usuario' => $_SESSION['emp_auth']['id_usuario'],
            'rol_usuario' => $_SESSION['emp_auth']['rol_usuario']
        ]);
        header('Location: ../interfaz/empleado/index.php');
        exit();

    case 6:
        $_SESSION['user_auth'] = [
            'id_usuario' => (int) $idUsuario,
            'nombre_usuario' => (string) $nombreUsuario,
            'rol_usuario' => $rolActual,
        ];
        Logger::registrarLog('INFO', 'El usuario ha iniciado sesion como cliente', [
            'id_usuario' => $_SESSION['user_auth']['id_usuario'],
            'rol_usuario' => $_SESSION['user_auth']['rol_usuario']
        ]);
        header('Location: ../interfaz_usu.php');
        exit();

    default:
        redirigir_login('error=rol');
}