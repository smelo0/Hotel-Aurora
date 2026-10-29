<?php

use App\Logger;

require __DIR__ . '/security/conexion.php';
require_once __DIR__ . '/audio_helper.php';

// ==========================================
// AUDIO DE FONDO (helper centralizado v4)
// ==========================================
// Limpiamos cualquier audio viejo que haya quedado atascado
stopBackgroundAudio();
usleep(500000); // Espera 0.5 segundos para que Windows libere la memoria

// Arrancamos el audio nuevo
startBackgroundAudio(getDefaultAudioFile());
register_shutdown_function('stopBackgroundAudio');


// ==========================================
// COLORES E ÍCONOS ANSI
// ==========================================
$rojo         = "\033[31m";
$verde        = "\033[32m";
$amarillo     = "\033[33m";
$azul         = "\033[34m";
$cyan         = "\033[36m";
$reset        = "\033[0m";
$verdeOliva   = "\033[38;5;64m";
$doradoOscuro = "\033[38;5;178m";

$iconoCandado = "  ";
$iconoCheck   = "  ";
$iconoError   = "  ";
$iconoUser    = "  ";
$iconoAdmin   = " 󰌆 ";

$trys = 0;

limpiarPantalla();
echo "{$doradoOscuro}
██╗░░██╗░█████╗░████████╗███████╗██╗░░░░░  ░█████╗░██╗░░░██╗██████╗░░█████╗░██████╗░░█████╗░
██║░░██║██╔══██╗╚══██╔══╝██╔════╝██║░░░░░  ██╔══██╗██║░░░██║██╔══██╗██╔══██╗██╔══██╗██╔══██╗
███████║██║░░██║░░░██║░░░█████╗░░██║░░░░░  ███████║██║░░░██║██████╔╝██║░░██║██████╔╝███████║
██╔══██║██║░░██║░░░██║░░░██╔══╝░░██║░░░░░  ██╔══██║██║░░░██║██╔══██╗██║░░██║██╔══██╗██╔══██║
██║░░██║╚█████╔╝░░░██║░░░███████╗███████╗  ██║░░██║╚██████╔╝██║░░██║╚█████╔╝██║░░██║██║░░██║
╚═╝░░╚═╝░╚════╝░░░░╚═╝░░░╚══════╝╚══════╝  ╚═╝░░╚═╝░╚═════╝░╚═╝░░╚═╝░╚════╝░╚═╝░░╚═╝╚═╝░░╚═╝{$reset}" . PHP_EOL;
echo "" . PHP_EOL;
echo "{$verdeOliva}
    ░██                                                     
    ░██                                                     
░████████  ░██████    ░██░████  ░███████    ░██████    ░███████  
    ░██         ░██  ░███     ░██    ░██       ░██  ░██        
    ░██    ░███████  ░██      ░█████████  ░███████    ░███████  
    ░██    ░██   ░██  ░██      ░██        ░██   ░██         ░██ 
     ░████  ░█████░██ ░██       ░███████   ░█████░██  ░███████{$reset}" . PHP_EOL;
echo "\n";

loading();
echo "\r\033[K";
echo "{$verde}✔ ¡Sistema listo y operativo!{$reset}\n\n";

$sessionLoged = false;

do {
    if ($trys >= 3) {
        stopBackgroundAudio();
        echo "{$rojo}{$iconoError} [¡Programa Cerrado!] Usted ha intentando iniciar sesion demasiadas veces. Verifique los datos e intente mas tarde.{$reset}" . PHP_EOL;
        Logger::registrarLog("WARMING", "[¡Programa Cerrado!] Usuario ha intentado iniciar demasiadas veces. || CLI");
        exit(2);
    }

    echo "{$amarillo}{$iconoCandado} Inicie sesión para continuar{$reset}" . PHP_EOL;
    $userEmailTyped = (string) trim(readline("Ingrese su correo: "));
    $passwordTyped  = leerPasswordPrompt();
    limpiarPantalla();

    try {
        $sql = "SELECT id_usu, nom_usu, corr_usu, psw_usu, cod_rol_usu FROM usuario WHERE corr_usu = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$userEmailTyped]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Usuario no encontrado.{$reset}" . PHP_EOL . PHP_EOL;
            Logger::registrarLog("ERROR", "[¡ACCESO DENEGADO!] Usuario no encontrado ({$userEmailTyped}) || CLI");
            $trys += 1;
        } elseif (!password_verify($passwordTyped, $usuario['psw_usu'])) {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Clave incorrecta.{$reset}" . PHP_EOL . PHP_EOL;
            Logger::registrarLog("ERROR", "[¡ACCESO DENEGADO!] Clave incorrecta para el usuario: {$userEmailTyped} || CLI");
            $trys += 1;
        } elseif ($usuario['cod_rol_usu'] != "1") {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Usuario no tiene el rol para este sistema.{$reset}" . PHP_EOL . PHP_EOL;
            Logger::registrarLog("ERROR", "[¡ACCESO DENEGADO!] Rol inválido para el usuario: {$userEmailTyped} || CLI");
            $trys += 1;
        } else {
            echo "\n{$verde}{$iconoCheck}[¡ACCESO PERMITIDO!] Bienvenid@, " . $usuario['nom_usu'] . "{$reset}\n";
            echo "\n";
            echo "{$azul}[¡INFORMACION!] Hubieron {$trys} intento(s) fallido(s) de iniciar sesion.{$reset}\n";

            // Registro de éxito en el Logger
            Logger::registrarLog("INFO", "[¡ACCESO PERMITIDO!] Inicio de sesión exitoso del usuario: " . $usuario['corr_usu'] . " || CLI");

            $sessionData = [
                'id_usu'      => $usuario['id_usu'],
                'corr_usu'    => $usuario['corr_usu'],
                'cod_rol_usu' => $usuario['cod_rol_usu'],
                'last_activity' => time() // <--- Inicializar aquí al hacer login
            ];
            file_put_contents(__DIR__ . '/session.json', json_encode($sessionData));

            require __DIR__ . "/options/selectTasks.php";
            $sessionLoged = true;
            break;
        }
    } catch (PDOException $e) {
        echo "{$rojo}Error en la consulta: " . $e->getMessage() . "{$reset}\n";
        Logger::registrarLog("ERROR", "Error de base de datos en inicio de sesión: " . $e->getMessage() . " || CLI");
    }
} while ($sessionLoged != true);