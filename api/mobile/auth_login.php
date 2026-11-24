<?php
/**
 * API REST Móvil — Autenticación Oficial
 * Hotel Aurora Sanctuary & Concierge
 * Conecta directamente a la base de datos MariaDB `hotel`
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../configuracion/conexion.php';

// Leer cuerpo de la petición (JSON o POST tradicional)
$rawInput = file_get_contents('php://input');
$body = json_decode($rawInput, true) ?? [];

$correo = trim((string) ($body['corr_usu'] ?? $_POST['corr_usu'] ?? ''));
$password = (string) ($body['psw_usu'] ?? $_POST['psw_usu'] ?? '');

if ($correo === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Por favor ingrese correo electrónico y contraseña.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Consultar usuario en la base de datos MariaDB
$sql = 'SELECT u.id_usu, u.nom_usu, u.corr_usu, u.psw_usu, u.cod_rol_usu, u.est_usu, r.des_rol AS rol_nombre 
        FROM usuario u 
        LEFT JOIN rol r ON r.cod_rol = u.cod_rol_usu 
        WHERE u.corr_usu = ? LIMIT 1';

$stmt = $conexion->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar consulta en la base de datos: ' . $conexion->error
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$stmt->bind_param('s', $correo);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows !== 1) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'El correo electrónico no se encuentra registrado en Hotel Aurora.'
    ], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    exit();
}

$usuario = $resultado->fetch_assoc();
$stmt->close();

// Verificar estado activo
if ((int) $usuario['est_usu'] !== 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Usuario inactivo. Contacte a la administración del hotel.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Verificar contraseña con Bcrypt
$hashAlmacenado = $usuario['psw_usu'];
if (!password_verify($password, $hashAlmacenado)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Contraseña incorrecta. Verifique sus credenciales.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Validación estricta de Roles para la app móvil
$codRol = (int) $usuario['cod_rol_usu'];

if ($codRol !== 1 && $codRol !== 6) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Rol no habilitado para la aplicación móvil. Ingrese vía web.'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Autenticación Exitosa
echo json_encode([
    'success' => true,
    'message' => 'Inicio de sesión exitoso.',
    'usuario' => [
        'id_usu' => (int) $usuario['id_usu'],
        'nom_usu' => (string) $usuario['nom_usu'],
        'corr_usu' => (string) $usuario['corr_usu'],
        'cod_rol_usu' => $codRol,
        'rol_nombre' => (string) ($usuario['rol_nombre'] ?? ($codRol === 1 ? 'Gerente General' : 'Cliente')),
        'est_usu' => (int) $usuario['est_usu']
    ]
], JSON_UNESCAPED_UNICODE);
