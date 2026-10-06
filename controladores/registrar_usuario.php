<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';

require_once __DIR__ . '/../configuracion/conexion.php';

// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;
use App\Recaptcha;

function redirectToUserLogin(string $query = ''): never
{
    $destination = '../interfaz/loggins/index_usu.php';
    if ($query !== '') {
        $destination .= '?' . $query;
    }

    header('Location: ' . $destination);
    exit();
}

if (!isset($conexion) || $conexion->connect_errno) {
    Logger::registrarLog('ERROR', 'Fallo de conexión a la base de datos en registro de usuario con consentimiento', [
        'error_db' => $conexion->connect_error ?? 'Desconocido'
    ]);
    redirectToUserLogin('vista=registro&error=conexion_fallida');
}

$conexion->set_charset('utf8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirectToUserLogin();
}

exigir_csrf();

$nombre = trim((string) ($_POST['nom_usu'] ?? ''));
$correo = trim((string) ($_POST['corr_usu'] ?? ''));
$passwordPura = (string) ($_POST['psw_usu'] ?? '');
$dataConsent = (int) ($_POST['data_consent'] ?? 0);
$captchaToken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
$consentIp = $_SERVER['REMOTE_ADDR'] ?? '';

if ($nombre === '' || $correo === '' || $passwordPura === '') {
    Logger::registrarLog('WARN', 'Intento de registro con campos vacíos', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=vacio');
}

if ($dataConsent !== 1) {
    Logger::registrarLog('WARN', 'Intento de registro sin aceptar el consentimiento de datos de privacidad', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=consentimiento');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    Logger::registrarLog('WARN', 'Intento de registro con formato de correo electrónico inválido', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=email');
}

if (Recaptcha::isEnabled() && !Recaptcha::verify($captchaToken)) {
    Logger::registrarLog('WARN', 'Fallo en la validación del token de reCAPTCHA durante el registro', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=captcha');
}

if (mb_strlen($nombre) > 140 || mb_strlen($passwordPura) < 12 || strlen($passwordPura) > 72) {
    redirectToUserLogin('vista=registro&error=contrasena');
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
    
    Logger::registrarLog('WARN', 'Intento de registro con un correo electrónico ya existente', [
        'correo' => $correo
    ]);
    
    redirectToUserLogin('vista=registro&error=correo_duplicado');
}

$stmtCheck->close();

$passwordEncriptada = password_hash($passwordPura, PASSWORD_BCRYPT);
$rolHuesped = 6;

$sql = 'INSERT INTO usuario (nom_usu, corr_usu, psw_usu, cod_rol_usu, data_consent, consent_date, consent_ip) VALUES (?, ?, ?, ?, ?, NOW(), ?)';
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    Logger::registrarLog('ERROR', 'Fallo al preparar inserción de nuevo usuario con consentimiento', [
        'error_db' => $conexion->error
    ]);
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_insercion');
}

$stmt->bind_param('sssiis', $nombre, $correo, $passwordEncriptada, $rolHuesped, $dataConsent, $consentIp);

if (!$stmt->execute()) {
    Logger::registrarLog('ERROR', 'Fallo al ejecutar inserción de nuevo usuario con consentimiento', [
        'error_db' => $stmt->error
    ]);
    $stmt->close();
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_ejecucion');
}

$nuevoIdUsuario = $stmt->insert_id;
$stmt->close();
$conexion->close();

// LOG DE ÉXITO: Usuario registrado correctamente con consentimiento legal y registro de IP
Logger::registrarLog('INFO', 'Nuevo usuario registrado exitosamente con consentimiento de datos', [
    'id_usuario' => $nuevoIdUsuario,
    'correo' => $correo,
    'consentimiento_ip' => $consentIp
]);

redirectToUserLogin('exito=registrado');