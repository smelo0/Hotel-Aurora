<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once '../configuracion/conexion.php';

// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

function redirigir_registro_legacy(string $query = ''): never
{
    $destino = '../interfaz/loggins/registro_usu.php';
    if ($query !== '') {
        $destino .= '?' . $query;
    }

    header('Location: ' . $destino);
    exit();
}

if (!isset($conexion) || $conexion->connect_errno) {
    Logger::registrarLog('ERROR', 'Fallo de conexión a la base de datos en registro legacy', [
        'error_db' => $conexion->connect_error ?? 'Desconocido'
    ]);
    redirigir_registro_legacy('error=conexion_fallida');
}

$conexion->set_charset('utf8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir_registro_legacy();
}

$nombre = trim((string) ($_POST['nombre'] ?? ''));
$tipoDocumento = trim((string) ($_POST['tipo'] ?? ''));
$numDocumento = trim((string) ($_POST['num_documento'] ?? ''));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$correo = trim((string) ($_POST['correo'] ?? ''));
$contrasena = (string) ($_POST['contrasena'] ?? '');
$idRol = 6;

if (
    $nombre === '' ||
    $tipoDocumento === '' ||
    $numDocumento === '' ||
    $telefono === '' ||
    $correo === '' ||
    $contrasena === ''
) {
    Logger::registrarLog('WARN', 'Intento de registro legacy con campos vacíos', ['correo' => $correo]);
    redirigir_registro_legacy('error=vacio');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    Logger::registrarLog('WARN', 'Intento de registro legacy con formato de correo inválido', ['correo' => $correo]);
    redirigir_registro_legacy('error=email');
}

$sqlCheck = 'SELECT id_usu FROM usuario WHERE corr_usu = ? LIMIT 1';
$stmtCheck = $conexion->prepare($sqlCheck);

if (!$stmtCheck) {
    Logger::registrarLog('ERROR', 'Fallo al preparar consulta SQL de verificación de correo en registro legacy', [
        'error_db' => $conexion->error
    ]);
    $conexion->close();
    redirigir_registro_legacy('error=bd_preparacion');
}

$stmtCheck->bind_param('s', $correo);

if (!$stmtCheck->execute()) {
    Logger::registrarLog('ERROR', 'Fallo al ejecutar consulta SQL de verificación de correo en registro legacy', [
        'error_db' => $stmtCheck->error
    ]);
    $stmtCheck->close();
    $conexion->close();
    redirigir_registro_legacy('error=bd_ejecucion');
}

$stmtCheck->store_result();

if ($stmtCheck->num_rows > 0) {
    $stmtCheck->close();
    $conexion->close();
    
    Logger::registrarLog('WARN', 'Intento de registro legacy con un correo electrónico ya existente', [
        'correo' => $correo
    ]);
    
    redirigir_registro_legacy('error=correo_duplicado');
}

$stmtCheck->close();

$docUsuario = $tipoDocumento . ' ' . $numDocumento;
$passSegura = password_hash($contrasena, PASSWORD_BCRYPT);

$sqlInsert = 'INSERT INTO usuario (doc_usu, nom_usu, tel_usu, corr_usu, psw_usu, cod_rol_usu) VALUES (?, ?, ?, ?, ?, ?)';
$stmtInsert = $conexion->prepare($sqlInsert);

if (!$stmtInsert) {
    Logger::registrarLog('ERROR', 'Fallo al preparar inserción de nuevo usuario en registro legacy', [
        'error_db' => $conexion->error
    ]);
    $conexion->close();
    redirigir_registro_legacy('error=bd_insercion');
}

$stmtInsert->bind_param('sssssi', $docUsuario, $nombre, $telefono, $correo, $passSegura, $idRol);

if (!$stmtInsert->execute()) {
    Logger::registrarLog('ERROR', 'Fallo al ejecutar inserción de nuevo usuario en registro legacy', [
        'error_db' => $stmtInsert->error
    ]);
    $stmtInsert->close();
    $conexion->close();
    redirigir_registro_legacy('error=bd_ejecucion');
}

$nuevoIdUsuario = $stmtInsert->insert_id;

session_regenerate_id(true);
$_SESSION['user_auth'] = [
    'id_usuario' => (int) $nuevoIdUsuario,
    'nombre_usuario' => $nombre,
    'rol_usuario' => $idRol,
];

$stmtInsert->close();
$conexion->close();

// LOG DE ÉXITO: Registro exitoso e inicio de sesión automático inmediato
Logger::registrarLog('INFO', 'Nuevo usuario registrado vía legacy y sesión iniciada automáticamente', [
    'id_usuario' => $nuevoIdUsuario,
    'correo' => $correo
]);

header('Location: ../interfaz_usu.php');
exit();