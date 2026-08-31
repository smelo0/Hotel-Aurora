<?php
// 1. Cargar el autoloader de Composer (ajusta la ruta relativa si es necesario)
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// 2. Inicializar y cargar las variables del archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../'); // Ajusta la ruta a la carpeta donde está tu archivo .env
$dotenv->load();

// Configuracion de base de datos con soporte para variables de entorno.
// En XAMPP/Windows, 127.0.0.1 evita bloqueos ocasionales de resolucion con "localhost".
$servidor = $_ENV['DB_HOST'];
$usuario =  $_ENV['DB_USER'];
$password =  $_ENV['DB_PASS'];
$base_datos =  $_ENV['DB_NAME'];
$puerto = (int)  $_ENV['DB_PORT'];
$timeout = (int)  $_ENV['DB_TIMEOUT'];

mysqli_report(MYSQLI_REPORT_OFF);

function responder_error_conexion_bd($mensaje) {
    http_response_code(500);

    $aceptaJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

    if ($aceptaJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'mensaje' => $mensaje
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    die(
        '<div style="font-family: Arial, sans-serif; max-width: 720px; margin: 48px auto; padding: 24px; border: 1px solid #fecaca; border-radius: 12px; background: #fff7f7; color: #7f1d1d;">'
        . '<h2 style="margin-top: 0;">No se pudo conectar con la base de datos</h2>'
        . '<p>' . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>Abre el Panel de Control de XAMPP e inicia <strong>MySQL</strong>. Luego recarga esta pagina.</p>'
        . '</div>'
    );
}

$conexion = mysqli_init();
if (!$conexion) {
    responder_error_conexion_bd('No se pudo inicializar la conexion MySQL.');
}

$conexion->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);

$conectado = @$conexion->real_connect(
    $servidor,
    $usuario,
    $password,
    $base_datos,
    $puerto,
    null,
    MYSQLI_CLIENT_FOUND_ROWS
);

if (!$conectado || $conexion->connect_errno) {
    responder_error_conexion_bd(
        'MySQL rechazo la conexion en ' . $servidor . ':' . $puerto
        . '. Verifica que MySQL este iniciado en XAMPP y que exista la base de datos "' . $base_datos . '".'
    );
}

if (!$conexion->set_charset('utf8mb4')) {
    responder_error_conexion_bd('No se pudo configurar utf8mb4 en la conexion MySQL.');
}

$conexion->query("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
?>
