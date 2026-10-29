<?php

require __DIR__ . '/security/conexion.php';

// ==========================================
// CONFIGURACIÓN DE AUDIO DE FONDO NATIVA (WINDOWS)
// ==========================================
$audioFile = __DIR__ . "/sonido.wav";

function startBackgroundAudio($file) {
    if (!file_exists($file)) return; 
    $os = PHP_OS_FAMILY;
    $absolutePath = realpath($file);

    if ($os === 'Windows') {
        // Reproduce el MP3 en segundo plano usando .NET MediaPlayer nativo de Windows sin instalar nada
        $psCommand = "Add-Type -AssemblyName presentationcore; \$player = New-Object System.Windows.Media.MediaPlayer; \$player.Open('$absolutePath'); \$player.Play(); Start-Sleep -Seconds 35;";
        pclose(popen("start /b powershell -WindowStyle Hidden -Command \"$psCommand\" >nul 2>&1", "r"));
    } else {
        // Compatible con Linux/Mac si se usa ffplay
        exec("ffplay -nodisp -loop 0 -loglevel quiet " . escapeshellarg($file) . " > /dev/null 2>&1 &");
    }
}

function stopBackgroundAudio() {
    $os = PHP_OS_FAMILY;
    if ($os === 'Windows') {
        // Cierra los procesos de PowerShell de audio al salir o ingresar
        system("taskkill /f /im powershell.exe >nul 2>&1");
    } else {
        system("pkill -f ffplay > /dev/null 2>&1");
    }
}

// Iniciar música de fondo al arrancar el script
startBackgroundAudio($audioFile);

// Registrar función de apagado automático si el script finaliza
register_shutdown_function('stopBackgroundAudio');


// ==========================================
// DEFINICIÓN DE COLORES E ÍCONOS ANSI
// ==========================================
$rojo          = "\033[31m";
$verde         = "\033[32m";
$amarillo      = "\033[33m";
$azul          = "\033[34m";
$cyan          = "\033[36m";
$reset         = "\033[0m";
$verdeOliva    = "\033[38;5;64m";
$doradoOscuro  = "\033[38;5;178m";

$iconoCandado = "  "; 
$iconoCheck   = "  "; 
$iconoError   = "  "; 
$iconoUser    = "  "; 
$iconoAdmin   = " 󰌆 "; 

$trys = 0;

// Arte ASCII en dorado
limpiarPantalla();
echo 
"{$doradoOscuro}
██╗░░██╗░█████╗░████████╗███████╗██╗░░░░░  ░█████╗░██╗░░░██╗██████╗░░█████╗░██████╗░░█████╗░
██║░░██║██╔══██╗╚══██╔══╝██╔════╝██║░░░░░  ██╔══██╗██║░░░██║██╔══██╗██╔══██╗██╔══██╗██╔══██╗
███████║██║░░██║░░░██║░░░█████╗░░██║░░░░░  ███████║██║░░░██║██████╔╝██║░░██║██████╔╝███████║
██╔══██║██║░░██║░░░██║░░░██╔══╝░░██║░░░░░  ██╔══██║██║░░░██║██╔══██╗██║░░██║██╔══██╗██╔══██║
██║░░██║╚█████╔╝░░░██║░░░███████╗███████╗  ██║░░██║╚██████╔╝██║░░██║╚█████╔╝██║░░██║██║░░██║
╚═╝░░╚═╝░╚════╝░░░░╚═╝░░░╚══════╝╚══════╝  ╚═╝░░╚═╝░╚╚═════╝░╚═╝░░╚═╝░╚════╝░╚═╝░░╚═╝╚═╝░░╚═╝9{$reset}" . PHP_EOL;
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
    // Validación previa de intentos antes de pedir credenciales
    if ($trys >= 3) {
        stopBackgroundAudio(); // Apaga la música si se bloquea por intentos fallidos
        echo "{$rojo}{$iconoError} [¡Programa Cerrado!] Usted ha intentando iniciar sesion demasiadas veces. Verifique los datos e intente mas tarde.{$reset}" . PHP_EOL;
        exit(2);
    }

    echo "{$amarillo}{$iconoCandado} Inicie sesión para continuar{$reset}" . PHP_EOL;
    $userEmailTyped = (string) trim(readline("Ingrese su correo: "));
    $passwordTyped = leerPasswordPrompt();
    limpiarPantalla();

    try {
        $sql = "SELECT id_usu, nom_usu, corr_usu, psw_usu, cod_rol_usu FROM usuario WHERE corr_usu = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$userEmailTyped]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 1. Validar si el usuario no existe
        if (!$usuario) {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Usuario no encontrado.{$reset}" . PHP_EOL . PHP_EOL;
            $trys += 1; 
        } 
        // 2. Si la clave es incorrecta
        elseif (!password_verify($passwordTyped, $usuario['psw_usu'])) {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Clave incorrecta.{$reset}" . PHP_EOL . PHP_EOL;
            $trys += 1;
        } 
        // 3. Si el rol no es el de administrador ("1")
        elseif ($usuario['cod_rol_usu'] != "1") {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Usuario no tiene el rol para este sistema.{$reset}" . PHP_EOL . PHP_EOL;
            $trys += 1;
        } 
        // 4. Si todo es correcto -> ACCESO PERMITIDO
        else {
            // Apagamos la música de fondo al ingresar exitosamente
            stopBackgroundAudio(); 

            echo "\n{$verde}{$iconoCheck} [¡ACCESO PERMITIDO!] Bienvenid@, " . $usuario['nom_usu'] . "{$reset}\n";
            echo "{$azul}[¡INFORMACION!] Hubieron {$trys} intento(s) fallido(s) de iniciar sesion.{$reset}\n";
            
            $sessionData = [
                'id_usu' => $usuario['id_usu'],
                'corr_usu' => $usuario['corr_usu'],
                'cod_rol_usu' => $usuario['cod_rol_usu']
            ];
    
            file_put_contents(__DIR__ . '/session.json', json_encode($sessionData));
    
            require __DIR__ . "/options/selectTasks.php";
            $sessionLoged = true;
            break;
        }

    } catch (PDOException $e) {
        echo "{$rojo}Error en la consulta: " . $e->getMessage() . "{$reset}\n";
    }

} while ($sessionLoged != true);