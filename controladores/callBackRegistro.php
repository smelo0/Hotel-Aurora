<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';

require_once '../configuracion/conexion.php';
// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

function validarRecaptcha(string $token): bool
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

function redirectToUserLogin(string $query = ''): never
{
    $destination = '../interfaz/loggins/loginUsuario.php';
    if ($query !== '') {
        $destination .= '?' . $query;
    }

    header('Location: ' . $destination);
    exit();
}

if (!isset($conexion) || $conexion->connect_errno) {
    Logger::registrarLog('ERROR', 'Fallo de conexión a la base de datos en registro de usuario', [
        'error_db' => $conexion->connect_error ?? 'Desconocido'
    ]);
    redirectToUserLogin('vista=registro&error=conexion_fallida');
}

$conexion->set_charset('utf8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirectToUserLogin();
}

$nombre = trim((string) ($_POST['nom_usu'] ?? ''));
$correo = trim((string) ($_POST['corr_usu'] ?? ''));
$passwordPura = (string) ($_POST['psw_usu'] ?? '');
$captchaToken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
$captchaMarcado = (string) ($_POST['captcha'] ?? '');

if ($nombre === '' || $correo === '' || $passwordPura === '') {
    Logger::registrarLog('WARN', 'Intento de registro con campos vacíos', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=vacio');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    Logger::registrarLog('WARN', 'Intento de registro con formato de correo inválido', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=email');
}

if ($captchaMarcado !== 'on' && $captchaToken === '') {
    Logger::registrarLog('WARN', 'Intento de registro omitiendo el reCAPTCHA', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=captcha');
}

if ($captchaToken !== '' && !validarRecaptcha($captchaToken)) {
    Logger::registrarLog('WARN', 'Fallo en la validación del token de reCAPTCHA durante el registro', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=captcha');
}

$sqlCheck = 'SELECT id_usu FROM usuario WHERE corr_usu = ? LIMIT 1';
$stmtCheck = $conexion->prepare($sqlCheck);

if (!$stmtCheck) {
    Logger::registrarLog('ERROR', 'Fallo al preparar consulta SQL para verificar correo duplicado en registro', [
        'error_db' => $conexion->error
    ]);
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_preparacion');
}

$stmtCheck->bind_param('s', $correo);

if (!$stmtCheck->execute()) {
    Logger::registrarLog('ERROR', 'Fallo al ejecutar consulta SQL para verificar correo duplicado en registro', [
        'error_db' => $stmtCheck->error
    ]);
    $stmtCheck->close();
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_ejecucion');
}

$stmtCheck->store_result();

if ($stmtCheck->num_rows > 0) {
    $stmtCheck->close();
    $conexion->close();
    
    // Log de advertencia por intento de duplicar cuenta
    Logger::registrarLog('WARN', 'Intento de registro con un correo electrónico ya existente', [
        'correo' => $correo
    ]);
    
    redirectToUserLogin('vista=registro&error=correo_duplicado');
}

$stmtCheck->close();

$passwordEncriptada = password_hash($passwordPura, PASSWORD_BCRYPT);
$rolHuesped = 6;

$sql = 'INSERT INTO usuario (nom_usu, corr_usu, psw_usu, cod_rol_usu) VALUES (?, ?, ?, ?)';
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    Logger::registrarLog('ERROR', 'Fallo al preparar inserción de nuevo usuario en base de datos', [
        'error_db' => $conexion->error
    ]);
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_insercion');
}

$stmt->bind_param('sssi', $nombre, $correo, $passwordEncriptada, $rolHuesped);

if (!$stmt->execute()) {
    Logger::registrarLog('ERROR', 'Fallo al ejecutar inserción de nuevo usuario en base de datos', [
        'error_db' => $stmt->error
    ]);
    $stmt->close();
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_ejecucion');
}

$nuevoIdUsuario = $stmt->insert_id;
$stmt->close();
$conexion->close();

// LOG DE ÉXITO: Nuevo usuario registrado manualmente con éxito
Logger::registrarLog('INFO', 'Nuevo usuario registrado exitosamente de forma manual', [
    'id_usuario' => $nuevoIdUsuario,
    'correo' => $correo
]);

redirectToUserLogin('exito=registrado');
?>