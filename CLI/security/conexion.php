
<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../styles/leerPassword.php';
require __DIR__ . '/../styles/banner.php';
require __DIR__ . '/../styles/limpiarPantalla.php';
require __DIR__ . '/../styles/loading.php';
// conexion.php

use Dotenv\Dotenv;
// 2. Inicializar y cargar las variables del archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../../'); // Ajusta la ruta a la carpeta donde está tu archivo .env
$dotenv->load();

if (php_sapi_name() !== 'cli') {
    die("Este script solo se puede ejecutar desde la línea de comandos.\n");
}

$host = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$usuario_db = $_ENV['DB_USER'];
$password_db = $_ENV['DB_PASS'];

try {
    $conexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $usuario_db, $password_db);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage() . "\n");
}