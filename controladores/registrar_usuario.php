<?php
declare(strict_types=1);

session_start();

require_once '../configuracion/conexion.php';

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
    $destination = '../interfaz/loggins/index_usu.php';
    if ($query !== '') {
        $destination .= '?' . $query;
    }

    header('Location: ' . $destination);
    exit();
}

if (!isset($conexion) || $conexion->connect_errno) {
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
    redirectToUserLogin('vista=registro&error=vacio');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    redirectToUserLogin('vista=registro&error=email');
}

if ($captchaMarcado !== 'on' && $captchaToken === '') {
    redirectToUserLogin('vista=registro&error=captcha');
}

if ($captchaToken !== '' && !validarRecaptcha($captchaToken)) {
    redirectToUserLogin('vista=registro&error=captcha');
}

$sqlCheck = 'SELECT id_usu FROM usuario WHERE corr_usu = ? LIMIT 1';
$stmtCheck = $conexion->prepare($sqlCheck);

if (!$stmtCheck) {
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_preparacion');
}

$stmtCheck->bind_param('s', $correo);

if (!$stmtCheck->execute()) {
    $stmtCheck->close();
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_ejecucion');
}

$stmtCheck->store_result();

if ($stmtCheck->num_rows > 0) {
    $stmtCheck->close();
    $conexion->close();
    redirectToUserLogin('vista=registro&error=correo_duplicado');
}

$stmtCheck->close();

$passwordEncriptada = password_hash($passwordPura, PASSWORD_BCRYPT);
$rolHuesped = 6;

$sql = 'INSERT INTO usuario (nom_usu, corr_usu, psw_usu, cod_rol_usu) VALUES (?, ?, ?, ?)';
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_insercion');
}

$stmt->bind_param('sssi', $nombre, $correo, $passwordEncriptada, $rolHuesped);

if (!$stmt->execute()) {
    $stmt->close();
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_ejecucion');
}

$stmt->close();
$conexion->close();

redirectToUserLogin('exito=registrado');
