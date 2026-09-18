<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';

require_once '../vendor/autoload.php';
require_once '../configuracion/conexion.php';

// 1. Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

function redirigir_login(string $query = ''): never
{
    $destino = '../interfaz/loggins/loginUsuario.php';
    if ($query !== '') {
        $destino .= '?' . $query;
    }
    header('Location: ' . $destino);
    exit();
}

// Validar que la conexión esté activa
if (!isset($conexion) || $conexion->connect_errno) {
    redirigir_login('error=conexion_fallida');
}

$conexion->set_charset('utf8');

// 2. Recepcionar el token que envía Google por POST
$idToken = $_POST['credential'] ?? '';

if (empty($idToken)) {
    redirigir_login('error=vacio');
}

// 3. Validar el token de Google
$clientID = $_ENV['GOOGLE_CLIENT_ID'] ?? '';
$client = new Google_Client(['client_id' => $clientID]);

try {
    $payload = $client->verifyIdToken($idToken);
} catch (Exception $e) {
    $conexion->close();
    redirigir_login('error=token_invalido');
}

if (!$payload) {
    $conexion->close();
    redirigir_login('error=credenciales');
}

// 4. Extraer los datos recepcionados de Google
$correoGoogle = filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL);
$nombreGoogle = (string) ($payload['name'] ?? 'Usuario Google');

if (!$correoGoogle) {
    $conexion->close();
    redirigir_login('error=email');
}

// 5. Consultar en la BD si el correo ya está registrado
$sql = 'SELECT id_usu, nom_usu, cod_rol_usu FROM usuario WHERE corr_usu = ? LIMIT 1';
$stmt = $conexion->prepare($sql);

if (!$stmt) {
    $conexion->close();
    redirigir_login('error=bd_preparacion');
}

$stmt->bind_param('s', $correoGoogle);

if (!$stmt->execute()) {
    $stmt->close();
    $conexion->close();
    redirigir_login('error=bd_ejecucion');
}

$stmt->store_result();

// --- OPCIÓN A: El usuario YA existe en la base de datos ---
if ($stmt->num_rows === 1) {
    $stmt->bind_result($idUsuario, $nombreUsuario, $rolUsuario);
    $stmt->fetch();
    $stmt->close();
} else {
    // --- OPCIÓN B: El usuario NO existe (Registro automático con Rol 6) ---
    $stmt->close();
    
    $rolPorDefecto = 6; // Rol de cliente requerido por tu sistema
    $sqlInsert = 'INSERT INTO usuario (nom_usu, corr_usu, cod_rol_usu, psw_usu) VALUES (?, ?, ?, "")';
    $stmtInsert = $conexion->prepare($sqlInsert);

    if (!$stmtInsert) {
        $conexion->close();
        redirigir_login('error=bd_preparacion');
    }

    $stmtInsert->bind_param('ssi', $nombreGoogle, $correoGoogle, $rolPorDefecto);

    if (!$stmtInsert->execute()) {
        $stmtInsert->close();
        $conexion->close();
        redirigir_login('error=bd_ejecucion');
    }

    $idUsuario = $stmtInsert->insert_id;
    $nombreUsuario = $nombreGoogle;
    $rolUsuario = $rolPorDefecto;
    $stmtInsert->close();
}

$conexion->close();

// 6. Validar que tenga el rol 6 permitido por tu sistema
if ((int) $rolUsuario !== 6) {
    redirigir_login('error=rol');
}

// 7. Crear exactamente la sesión que espera tu interfaz_usu.php
session_regenerate_id(true);
$_SESSION['user_auth'] = [
    'id_usuario'     => (int) $idUsuario,
    'nombre_usuario' => (string) $nombreUsuario,
    'rol_usuario'    => (int) $rolUsuario,
];

// Redirigir al panel principal
header('Location: ../interfaz_usu.php');
exit();

?>