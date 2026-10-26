<?php
require __DIR__ . '/security/conexion.php';


// Definición de colores ANSI
$rojo           = "\033[31m";
$verde          = "\033[32m";
$amarillo       = "\033[33m";
$azul           = "\033[34m";
$cyan           = "\033[36m";
$reset          = "\033[0m";
$verdeOliva     = "\033[38;5;64m";
$doradoOscuro   = "\033[38;5;178m";

// Íconos de Nerd Fonts
$iconoCandado = "  "; 
$iconoCheck   = "  "; 
$iconoError   = "  "; 
$iconoUser    = "  "; 
$iconoAdmin   = " 󰌆 "; 

// Arte ASCII en dorado
echo "{$doradoOscuro}⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⣟⣿⣽⣯⣷⣿⣻⣾⣿⣻⣾⡿⣽⣿⣻⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⢿⣷⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣿⣽⡿⣷⣿⣿⣾⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⢿⡭⣗⣲⣶⣷⣒⣺⢭⡛⢿⣿⣿⣟⣾⣯⣿⢿⣾⣿
⣿⣿⣿⣿⣿⣿⣿⣿⢻⢞⣵⣿⣿⣿⣿⣿⣿⣿⣿⣮⡳⣝⣿⣿⣿⡷⣿⣿⢿⣿
⣿⣿⣿⣿⣿⣿⣿⣳⢻⣿⣿⣿⣿⣿⠟⠻⢿⣿⣿⣿⣿⡞⣌⣿⣿⣿⣿⣽⣿⣿
⣿⣿⣿⣿⣿⣿⡯⣏⣿⣿⣿⣿⣿⡇⢸⣷⠈⣿⣿⣿⣿⣿⠸⡸⣿⣿⣿⣾⣿⣽
⣿⣿⣿⣿⣿⣿⣧⣗⣤⣙⠻⢿⣿⡇⢸⣿⠀⣿⣿⠟⣉⣤⡆⡇⣿⣿⣿⣾⣿⣻
⣿⣿⣿⣿⣿⣿⡷⡏⣿⣿⣷⣦⠙⣧⠘⢋⣴⠏⣠⣾⣿⣿⡇⡏⣿⣿⣿⣷⣿⣿
⣿⣿⣿⣿⣿⣿⡿⣇⣍⣛⠛⣿⣷⡘⢷⣾⢁⣾⡿⠟⢋⣡⣬⡗⣿⣿⣿⣯⣿⣿
⣿⣿⣿⣿⣿⣿⣟⣯⣿⣿⣷⣄⠹⣷⠘⠇⣾⠋⣠⣾⣿⣿⣿⡯⣿⣿⣿⣿⣿⣽
⣿⣿⣿⣿⣿⣿⡟⡜⣿⣿⣿⣿⣧⠘⠇⠰⠇⣾⣿⣿⣿⣿⣿⡷⣿⣿⣿⣷⣿⣿
⣿⣿⣿⣿⣿⣿⣇⣇⣿⣿⣿⠟⣡⣾⡇⢸⣷⣌⠻⣿⣿⣿⣱⢹⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⡻⡼⣿⣿⣶⣿⣿⣇⢸⣿⣿⣶⣿⣿⣯⢯⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⣿⣽⣮⡻⣿⣿⣿⣷⣾⣿⣿⣿⢟⡵⣫⣿⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣾⣟⡯⣟⢻⢛⢻⡽⣺⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿
⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿{$reset}" . PHP_EOL;
echo "" . PHP_EOL;
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "{$verdeOliva}===== {$iconoAdmin} Bienvenido al panel de tareas del administrador ====={$reset}" . PHP_EOL;
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "" . PHP_EOL;

// Inicializamos la variable de control fuera do bucle
$sessionLoged = false;

do {
    echo "{$amarillo}{$iconoCandado} Inicie sesión para continuar{$reset}" . PHP_EOL;
    $userEmailTyped = (string) trim(readline("Ingrese su correo: "));
    $passwordTyped = leerPasswordPrompt();
    limpiarPantalla();

    // Consulta a la base de datos
    try {
        $sql = "SELECT id_usu, nom_usu, corr_usu, psw_usu, cod_rol_usu FROM usuario WHERE corr_usu = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([$userEmailTyped]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // 1. Validar si el usuario no existe
        if (!$usuario) {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Usuario no encontrado.{$reset}" . PHP_EOL . PHP_EOL;
        } 
        // 2. Si la clave es incorrecta
        elseif (!password_verify($passwordTyped, $usuario['psw_usu'])) {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Clave incorrecta.{$reset}" . PHP_EOL . PHP_EOL;
        } 
        // 3. Si el rol no es el de administrador ("1")
        elseif ($usuario['cod_rol_usu'] != "1") {
            echo "{$rojo}{$iconoError} [¡ACCESO DENEGADO!] Usuario no tiene el rol para este sistema.{$reset}" . PHP_EOL . PHP_EOL;
        } 
        // 4. Si todo es correcto -> ACCESO PERMITIDO
        else {
            echo "\n{$verde}{$iconoCheck} [¡ACCESO PERMITIDO!] Bienvenid@, " . $usuario['nom_usu'] . "{$reset}\n";

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