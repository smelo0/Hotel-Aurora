<?php
require __DIR__ . "/conexion.php";
require_once __DIR__ . "/../audio_helper.php";


$sessionFile = __DIR__ . '/../session.json';
$sessionData = json_decode(file_get_contents($sessionFile), true);
$userId = $sessionData['id_usu'] ?? null;
use App\Logger;

// Función para eliminar el archivo de sesión
function limpiarSesion() {
    $sessionFile = __DIR__ . '/../session.json';
    if (file_exists($sessionFile)) {
        @unlink($sessionFile);
    }
}

// 1. Captura salidas normales del script o errores fatales
register_shutdown_function('limpiarSesion');

// 2. Captura Ctrl + C en Windows (corta también el audio de fondo)
if (function_exists('sapi_windows_set_ctrl_handler')) {
    sapi_windows_set_ctrl_handler(function(int $event) {
        if ($event === PHP_WINDOWS_EVENT_CTRL_C) {
            limpiarSesion();
            stopBackgroundAudio();
            
            $rojo         = "\033[31m";
            $reset        = "\033[0m";
            exit("\n{$rojo}[SESIÓN CERRADA] Operación interrumpida por el usuario.{$reset}\n");
            Logger::registrarLog("INFO", "Operación interrumpida por el usuario. || User ID: $userID || CLI");
        }
    });
}

// 3. Verificar si existe el archivo de sesión
if (!file_exists($sessionFile)) {
    echo "\n{$rojo}[ERROR DE SEGURIDAD] No hay una sesión activa. Inicia sesión primero.{$reset}\n";
    Logger::registrarLog("WARNING", "Intento de abrir un modulo CLI sin iniciar sesion || CLI");
    exit(1);
}

$sessionData = json_decode(file_get_contents($sessionFile), true);
$userId = $sessionData['id_usu'] ?? null;
$lastActivity = $sessionData['last_activity'] ?? null;

if (!$userId) {
    echo "\n[ERROR DE SEGURIDAD] Sesión corrupta.\n";
    @unlink($sessionFile);
    exit(1);
}

// 4. VALIDACIÓN DE TIMEOUT POR INACTIVIDAD (5 minutos = 300 segundos)
$tiempo_inactividad = 300; // Pon 10 para probar, luego cámbialo a 300

if (isset($lastActivity) && $lastActivity > 0) {
    $segundos_transcurridos = time() - $lastActivity;

    if ($segundos_transcurridos > $tiempo_inactividad) {
        echo "\n{$azul}[SESIÓN EXPIRADA] La sesión ha expirado por inactividad.{$reset}\n";
        Logger::registrarLog("INFO", "La sesión ha expirado por inactividad. || User ID: $userId || CLI");
        @unlink($sessionFile);
        // Usamos die() en lugar de exit para garantizar que muera aquí mismo el proceso CLI
        die(1); 
    }
};

// 5. Verificar en la base de datos si el usuario sigue existiendo y es rol #1
try {
    global $conexion;

    $sql = "SELECT id_usu, cod_rol_usu FROM usuario WHERE id_usu = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$userId]);
    $usuarioActual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioActual || $usuarioActual['cod_rol_usu'] != "1") {
        echo "\n{$azul}[ACCESO DENEGADO] Tu cuenta ya no es válida o perdiste los privilegios de rol #1.{$reset}\n";
        Logger::registrarLog("INFO", "Usuario ya no es válida o perdio los privilegios de rol #1. || User ID: $userId || CLI");
        @unlink($sessionFile);
        exit(1);
    }

} catch (PDOException $e) {
    echo "\n{$rojo}[ERROR] No se pudo validar la sesión con la base de datos: " . $e->getMessage() . "{$reset}\n";
    Logger::registrarLog("ERROR", "No se pudo validar la sesión con la base de datos: || CLI");
    exit(1);
}