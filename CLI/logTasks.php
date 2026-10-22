<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// 2. Inicializar y cargar las variables del archivo .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../'); // Ajusta la ruta a la carpeta donde está tu archivo .env
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

echo "" . PHP_EOL;
echo "*********************************************************" . PHP_EOL;
echo "=====Bienvenido al panel de tareas del administrador=====" . PHP_EOL;
echo "*********************************************************" . PHP_EOL;
echo "" . PHP_EOL;

// Inicializamos la variable de control fuera del bucle
$sessionLoged = false;

do {

    echo "Inicie sesión para continuar" . PHP_EOL;
    $userEmailTyped = (string) trim(readline("Ingrese su correo: "));
    $passwordTyped = (string) trim(readline("Ingrese su contraseña: "));

    try {
        $sql = "SELECT id_usu, nom_usu, corr_usu, psw_usu, cod_rol_usu FROM usuario WHERE corr_usu = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$userEmailTyped]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (($usuario && password_verify($passwordTyped, $usuario['psw_usu'])) && ($usuario['cod_rol_usu'] == "1")){
            echo "\n[¡ACCESO PERMITIDO!] Bienvenid@, " . $usuario['nom_usu'] . "\n";
            
            require __DIR__ . "/options/selectTasks.php";
            $sessionLoged = true;
            break;
        
        } else {
            echo "Clave o usuario incorrecto " . PHP_EOL;
            echo "" . PHP_EOL;
        };
    } catch (PDOException $e) {
        echo "Error en la consulta: " . $e->getMessage() . "\n";
    }

} while ($sessionLoged != true);

