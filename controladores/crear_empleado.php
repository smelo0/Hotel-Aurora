<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
header('Content-Type: application/json; charset=utf-8');

function responder_crear_empleado(int $codigo, string $mensaje, array $datos = []): never
{
    http_response_code($codigo);
    echo json_encode(
        array_merge(['status' => $codigo < 300 ? 'exito' : 'error', 'mensaje' => $mensaje], $datos),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$idAdministrador = (int) ($_SESSION['emp_auth']['id_usuario'] ?? 0);
$rolAdministrador = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);
if ($idAdministrador < 1 || $rolAdministrador !== 1) {
    http_response_code(403);
    responder_crear_empleado(403, 'Solo el gerente general puede crear cuentas de empleado.');
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    responder_crear_empleado(405, 'Método no permitido.');
}

exigir_csrf();

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$correo = strtolower(trim((string) ($_POST['correo'] ?? '')));
$contrasena = (string) ($_POST['contrasena'] ?? '');

if (
    $nombre === ''
    || mb_strlen($nombre) > 100
    || !filter_var($correo, FILTER_VALIDATE_EMAIL)
    || strlen($correo) > 100
    || strlen($contrasena) < 12
    || strlen($contrasena) > 72
) {
    responder_crear_empleado(422, 'Revisa el nombre, el correo y la contraseña (12 a 72 caracteres).');
}

require_once __DIR__ . '/../configuracion/conexion.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Logger;

$consultaCorreo = $conexion->prepare('SELECT id_usu FROM usuario WHERE corr_usu = ? LIMIT 1');
if (!$consultaCorreo) {
    Logger::registrarLog('ERROR', 'No se pudo preparar la validación del correo de empleado', [
        'id_usuario' => $idAdministrador,
        'error_db' => $conexion->error,
    ]);
    responder_crear_empleado(500, 'No se pudo validar el correo. Inténtalo de nuevo.');
}

$consultaCorreo->bind_param('s', $correo);
if (!$consultaCorreo->execute()) {
    Logger::registrarLog('ERROR', 'No se pudo consultar el correo de empleado', [
        'id_usuario' => $idAdministrador,
        'error_db' => $consultaCorreo->error,
    ]);
    $consultaCorreo->close();
    responder_crear_empleado(500, 'No se pudo validar el correo. Inténtalo de nuevo.');
}
$consultaCorreo->store_result();
$correoExiste = $consultaCorreo->num_rows > 0;
$consultaCorreo->close();

if ($correoExiste) {
    responder_crear_empleado(409, 'Ya existe una cuenta con ese correo electrónico.');
}

$contrasenaHash = password_hash($contrasena, PASSWORD_BCRYPT);
if ($contrasenaHash === false) {
    Logger::registrarLog('ERROR', 'No se pudo proteger la contraseña de una nueva cuenta de empleado', [
        'id_usuario' => $idAdministrador,
    ]);
    responder_crear_empleado(500, 'No se pudo crear la cuenta. Inténtalo de nuevo.');
}

$rolEmpleado = 3;
$estadoActivo = 1;
$insertar = $conexion->prepare(
    'INSERT INTO usuario (nom_usu, corr_usu, psw_usu, cod_rol_usu, est_usu) VALUES (?, ?, ?, ?, ?)'
);
if (!$insertar) {
    Logger::registrarLog('ERROR', 'No se pudo preparar la creación de una cuenta de empleado', [
        'id_usuario' => $idAdministrador,
        'error_db' => $conexion->error,
    ]);
    responder_crear_empleado(500, 'No se pudo crear la cuenta. Inténtalo de nuevo.');
}

$insertar->bind_param('sssii', $nombre, $correo, $contrasenaHash, $rolEmpleado, $estadoActivo);
if (!$insertar->execute()) {
    $errorDb = $insertar->error;
    $insertar->close();
    Logger::registrarLog('ERROR', 'Falló la creación de una cuenta de empleado', [
        'id_usuario' => $idAdministrador,
        'correo' => $correo,
        'error_db' => $errorDb,
    ]);
    responder_crear_empleado(500, 'No se pudo crear la cuenta. Inténtalo de nuevo.');
}

$idEmpleado = (int) $insertar->insert_id;
$insertar->close();
$conexion->close();

Logger::registrarLog('INFO', 'El gerente general creó una cuenta de empleado', [
    'id_usuario' => $idAdministrador,
    'id_empleado' => $idEmpleado,
    'correo' => $correo,
]);

responder_crear_empleado(201, 'Cuenta de empleado creada correctamente.', [
    'id_usuario' => $idEmpleado,
    'correo' => $correo,
]);
