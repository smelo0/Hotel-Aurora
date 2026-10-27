<?php
require __DIR__ . "/conexion.php";

$sessionFile = __DIR__ . '/../session.json';

// Función para eliminar el archivo de sesión
function limpiarSesion() {
    $sessionFile = __DIR__ . '/../session.json'; // Ajusta la ruta si es necesario
    if (file_exists($sessionFile)) {
        @unlink($sessionFile);
    }
}

// 1. Captura salidas normales del script o errores fatales (ej. cuando usas exit;)
register_shutdown_function('limpiarSesion');

// 2. Captura la interrupción por teclado (Ctrl + C) en Windows
if (function_exists('sapi_windows_set_ctrl_handler')) {
    sapi_windows_set_ctrl_handler(function(int $event) {
        if ($event === PHP_WINDOWS_EVENT_CTRL_C) {
            limpiarSesion();
            exit("\n[SESIÓN CERRADA] Operación interrumpida por el usuario.\n");
        }
    });
};

// 1. Verificar si existe el archivo de sesión
if (!file_exists($sessionFile)) {
    echo "\n[ERROR DE SEGURIDAD] No hay una sesión activa. Inicia sesión primero.\n";
    exit(1); // Saca al usuario del programa
}

$sessionData = json_decode(file_get_contents($sessionFile), true);
$userId = $sessionData['id_usu'] ?? null;

if (!$userId) {
    echo "\n[ERROR DE SEGURIDAD] Sesión corrupta.\n";
    @unlink($sessionFile);
    exit(1);
}

// 2. Verificar en la base de datos si el usuario sigue existiendo y es rol #1
try {
    // Asumimos que $conexion ya está disponible, o la incluyes/conectas aquí si es necesario
    global $conexion; 

    $sql = "SELECT id_usu, cod_rol_usu FROM usuario WHERE id_usu = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$userId]);
    $usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);

    // 3. Validar si el usuario ya no existe o ya no es rol #1
    if (!$usuarioActual || $usuarioActual['cod_rol_usu'] != "1") {
        echo "\n[ACCESO DENEGADO] Tu cuenta ya no es válida o perdiste los privilegios de rol #1.\n";
        
        // Destruir la sesión local
        @unlink($sessionFile);
        exit(1); // ¡Lo saca del programa!
    }

} catch (PDOException $e) {
    echo "\n[ERROR] No se pudo validar la sesión con la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}