<?php 
// seleccionar tarea
require_once __DIR__ . '/../security/auth_guard.php';


echo "" . PHP_EOL;
echo "{$amarillo}
░███     ░███                                  
░████   ░████                                  
░██░██ ░██░██  ░███████  ░████████  ░██    ░██ 
░██ ░████ ░██ ░██    ░██ ░██    ░██ ░██    ░██ 
░██  ░██  ░██ ░█████████ ░██    ░██ ░██    ░██ 
░██       ░██ ░██        ░██    ░██ ░██   ░███ 
░██       ░██  ░███████  ░██    ░██  ░█████ {$reset}" . PHP_EOL;
echo "{$verdeOliva}{$iconoAdmin}{$reset}" . PHP_EOL;

// Inicializamos la variable de control fuera del bucle
$sessionLoged = false;


$validOptions = ["1", "2", "3", "0"];

do {
// animacion de carga
loading();

// Mensaje final de éxito en la misma línea o salto limpio
echo "\r\033[K"; // Limpia la línea del spinner
echo "{$verde}✔ ¡Selector de tareas listo!{$reset}\n";
    echo "" . PHP_EOL;
    echo "1. Ver tareas" . PHP_EOL;
    echo "2. Agregar tareas" . PHP_EOL;
    echo "3. Volver a Iniciar Sesion" . PHP_EOL;
    echo "0. Cerrar sesion" . PHP_EOL;
    
    $selector = (string) readline("Seleccione una tarea para continuar: ");
    limpiarPantalla();
    if (!in_array($selector, $validOptions)) {
        echo "opcion no valida" . PHP_EOL;
        echo "ingrese una opcion valida" . PHP_EOL;
    } 

    switch ($selector) {
        case "1":
            require __DIR__ . "/viewTasks.php";
            break 2;
        case "2":
            require __DIR__ . "/addTasks.php";
            break 2;
        case "3":
            require __DIR__ . "/../logTasks.php";
            break 2;
        case "0":
            echo "Cerrando sesion tareas." . PHP_EOL;
            require_once __DIR__. '/../security/logout.php';
            exit(0);
    };


    

} while ($selector !== "0");
