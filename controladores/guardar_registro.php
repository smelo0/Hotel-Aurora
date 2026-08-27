<?php
declare(strict_types=1);

session_start();

require_once '../configuracion/conexion.php';

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
    redirigir_registro_legacy('error=vacio');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    redirigir_registro_legacy('error=email');
}

$sqlCheck = 'SELECT id_usu FROM usuario WHERE corr_usu = ? LIMIT 1';
$stmtCheck = $conexion->prepare($sqlCheck);

if (!$stmtCheck) {
    $conexion->close();
    redirigir_registro_legacy('error=bd_preparacion');
}

$stmtCheck->bind_param('s', $correo);

if (!$stmtCheck->execute()) {
    $stmtCheck->close();
    $conexion->close();
    redirigir_registro_legacy('error=bd_ejecucion');
}

$stmtCheck->store_result();

if ($stmtCheck->num_rows > 0) {
    $stmtCheck->close();
    $conexion->close();
    redirigir_registro_legacy('error=correo_duplicado');
}

$stmtCheck->close();

$docUsuario = $tipoDocumento . ' ' . $numDocumento;
$passSegura = password_hash($contrasena, PASSWORD_BCRYPT);

$sqlInsert = 'INSERT INTO usuario (doc_usu, nom_usu, tel_usu, corr_usu, psw_usu, cod_rol_usu) VALUES (?, ?, ?, ?, ?, ?)';
$stmtInsert = $conexion->prepare($sqlInsert);

if (!$stmtInsert) {
    $conexion->close();
    redirigir_registro_legacy('error=bd_insercion');
}

$stmtInsert->bind_param('sssssi', $docUsuario, $nombre, $telefono, $correo, $passSegura, $idRol);

if (!$stmtInsert->execute()) {
    $stmtInsert->close();
    $conexion->close();
    redirigir_registro_legacy('error=bd_ejecucion');
}

session_regenerate_id(true);
$_SESSION['user_auth'] = [
    'id_usuario' => (int) $stmtInsert->insert_id,
    'nombre_usuario' => $nombre,
    'rol_usuario' => $idRol,
];

$stmtInsert->close();
$conexion->close();

header('Location: ../interfaz_usu.php');
exit();
