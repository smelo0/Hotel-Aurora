<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../configuracion/conexion.php';

use App\Logger;
use App\Recaptcha;

function redirectGoogleLogin(string $query = ''): never
{
    $destination = '../interfaz/loggins/index_usu.php';
    if ($query !== '') {
        $destination .= '?' . $query;
    }

    header('Location: ' . $destination);
    exit();
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirectGoogleLogin();
}

if (!Recaptcha::hasValidGoogleCsrf()) {
    Logger::registrarLog('WARN', 'Inicio de sesión Google rechazado por token CSRF inválido');
    redirectGoogleLogin('error=token_invalido');
}

$idToken = $_POST['credential'] ?? '';
if (!is_string($idToken) || $idToken === '') {
    redirectGoogleLogin('error=vacio');
}

if (!isset($conexion) || $conexion->connect_errno) {
    redirectGoogleLogin('error=conexion_fallida');
}

$clientId = trim((string) (
    getenv('GOOGLE_CLIENT_ID')
    ?: ($_ENV['GOOGLE_CLIENT_ID'] ?? $_SERVER['GOOGLE_CLIENT_ID'] ?? '')
));
if ($clientId === '') {
    Logger::registrarLog('ERROR', 'Inicio de sesión Google no disponible: falta GOOGLE_CLIENT_ID');
    $conexion->close();
    redirectGoogleLogin('error=token_invalido');
}

$client = new Google_Client(['client_id' => $clientId]);
try {
    $payload = $client->verifyIdToken($idToken);
} catch (Throwable $error) {
    Logger::registrarLog('ERROR', 'Excepción al verificar token de Google', [
        'excepcion' => $error->getMessage(),
    ]);
    $conexion->close();
    redirectGoogleLogin('error=token_invalido');
}

if (!$payload) {
    $conexion->close();
    redirectGoogleLogin('error=credenciales');
}

$email = filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email || ($payload['email_verified'] ?? false) !== true) {
    $conexion->close();
    redirectGoogleLogin('error=email');
}

$stmt = $conexion->prepare(
    'SELECT id_usu, nom_usu, cod_rol_usu FROM usuario WHERE corr_usu = ? LIMIT 1'
);
if (!$stmt) {
    Logger::registrarLog('ERROR', 'No se pudo preparar la consulta de cuenta para el acceso Google', [
        'error_db' => $conexion->error,
    ]);
    $conexion->close();
    redirectGoogleLogin('error=bd_preparacion');
}

$stmt->bind_param('s', $email);
if (!$stmt->execute()) {
    Logger::registrarLog('ERROR', 'No se pudo consultar la cuenta para el acceso Google', [
        'error_db' => $stmt->error,
    ]);
    $stmt->close();
    $conexion->close();
    redirectGoogleLogin('error=bd_ejecucion');
}

$stmt->store_result();
if ($stmt->num_rows !== 1) {
    $stmt->close();
    $conexion->close();
    redirectGoogleLogin('vista=registro&error=google_registro');
}

$stmt->bind_result($userId, $userName, $userRole);
if (!$stmt->fetch()) {
    $stmt->close();
    $conexion->close();
    redirectGoogleLogin('error=bd_ejecucion');
}
$stmt->close();
$conexion->close();

if ((int) $userRole !== 6) {
    Logger::registrarLog('WARN', 'Acceso Google rechazado por rol no autorizado', [
        'id_usuario' => $userId,
        'rol_detectado' => $userRole,
    ]);
    redirectGoogleLogin('error=rol');
}

session_regenerate_id(true);
$_SESSION['user_auth'] = [
    'id_usuario' => (int) $userId,
    'nombre_usuario' => (string) $userName,
    'rol_usuario' => (int) $userRole,
];

Logger::registrarLog('INFO', 'Inicio de sesión exitoso mediante Google', [
    'id_usuario' => $userId,
    'rol_usuario' => $_SESSION['user_auth']['rol_usuario'],
]);

header('Location: ../interfaz_usu.php');
exit();
