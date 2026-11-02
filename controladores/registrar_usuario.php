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

// Garantiza que exista la columna del tipo de documento (compatible con MariaDB 10.4 / XAMPP).
$colTipoDoc = $conexion->query("SHOW COLUMNS FROM usuario LIKE 'tipo_doc_usu'");
if ($colTipoDoc instanceof mysqli_result) {
    $existeTipoDoc = $colTipoDoc->num_rows > 0;
    $colTipoDoc->free();
    if (!$existeTipoDoc) {
        $conexion->query("ALTER TABLE usuario ADD COLUMN tipo_doc_usu VARCHAR(10) DEFAULT NULL AFTER doc_usu");
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirectToUserLogin();
}

exigir_csrf();

$nombre = trim((string) ($_POST['nom_usu'] ?? ''));
$correo = trim((string) ($_POST['corr_usu'] ?? ''));
$tipoDocumento = strtoupper(trim((string) ($_POST['tipo_doc_usu'] ?? '')));
$documento = preg_replace('/[\s.\-]/', '', trim((string) ($_POST['doc_usu'] ?? ''))) ?? '';
$telefono = trim((string) ($_POST['tel_usu'] ?? ''));
$passwordPura = (string) ($_POST['psw_usu'] ?? '');
$dataConsent = (int) ($_POST['data_consent'] ?? 0);
$captchaToken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
$consentIp = $_SERVER['REMOTE_ADDR'] ?? '';

if ($nombre === '' || $correo === '' || $passwordPura === '' || $tipoDocumento === '' || $documento === '' || $telefono === '') {
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

$tiposDocumentoValidos = ['CC', 'CE', 'PAS', 'PPT', 'PEP'];
if (!in_array($tipoDocumento, $tiposDocumentoValidos, true)) {
    Logger::registrarLog('WARN', 'Intento de registro con tipo de documento inválido', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=tipo_doc');
}

// El pasaporte puede ser alfanumérico; las cédulas y permisos solo numéricos.
$documentoValido = $tipoDocumento === 'PAS'
    ? preg_match('/^[A-Za-z0-9]{4,20}$/', $documento) === 1
    : preg_match('/^[0-9]{4,20}$/', $documento) === 1;

if (!$documentoValido) {
    Logger::registrarLog('WARN', 'Intento de registro con número de documento inválido', ['correo' => $correo, 'tipo_doc' => $tipoDocumento]);
    redirectToUserLogin('vista=registro&error=documento');
}
$documento = strtoupper($documento);

$telefonoLimpio = preg_replace('/\s+/', '', $telefono) ?? '';
if (preg_match('/^\+?[0-9]{7,15}$/', $telefonoLimpio) !== 1) {
    Logger::registrarLog('WARN', 'Intento de registro con número de celular inválido', ['correo' => $correo]);
    redirectToUserLogin('vista=registro&error=telefono');
}
$telefono = $telefonoLimpio;

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

$stmtDoc = $conexion->prepare('SELECT id_usu FROM usuario WHERE tipo_doc_usu = ? AND doc_usu = ? LIMIT 1');
if ($stmtDoc) {
    $stmtDoc->bind_param('ss', $tipoDocumento, $documento);
    $stmtDoc->execute();
    $stmtDoc->store_result();
    $docDuplicado = $stmtDoc->num_rows > 0;
    $stmtDoc->close();

    if ($docDuplicado) {
        $conexion->close();
        Logger::registrarLog('WARN', 'Intento de registro con un documento ya existente', ['correo' => $correo, 'tipo_doc' => $tipoDocumento]);
        redirectToUserLogin('vista=registro&error=documento_duplicado');
    }
}

$passwordEncriptada = password_hash($passwordPura, PASSWORD_BCRYPT);
$rolHuesped = 6;

$sql = 'INSERT INTO usuario (nom_usu, tipo_doc_usu, doc_usu, tel_usu, corr_usu, psw_usu, cod_rol_usu, data_consent, consent_date, consent_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)';
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    Logger::registrarLog('ERROR', 'Fallo al preparar inserción de nuevo usuario con consentimiento', [
        'error_db' => $conexion->error
    ]);
    $conexion->close();
    redirectToUserLogin('vista=registro&error=bd_insercion');
}

$stmt->bind_param('ssssssiis', $nombre, $tipoDocumento, $documento, $telefono, $correo, $passwordEncriptada, $rolHuesped, $dataConsent, $consentIp);

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