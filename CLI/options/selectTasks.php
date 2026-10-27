<?php 
// seleccionar tarea
require_once __DIR__ . '/../security/auth_guard.php';


echo "" . PHP_EOL;
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "{$verdeOliva}===== {$iconoAdmin} Bienvenido al panel de tareas del administrador ====={$reset}" . PHP_EOL;
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "" . PHP_EOL;

// Inicializamos la variable de control fuera del bucle
$sessionLoged = false;


$validOptions = ["1", "2", "3", "0"];

do {
    
    echo "\n";
    echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
    echo "{$verdeOliva}===================Selector de tareas===================={$reset}" . PHP_EOL;
    echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;

    $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
$mensaje = "Iniciando módulos del sistema...";

for ($i = 0; $i < 20; $i++) {
    $frame = $frames[$i % count($frames)];
    // El \r permite sobrescribir la línea actual para dar efecto de movimiento
    echo "\r{$cyan}{$frame}{$reset} {$mensaje}";
    usleep(80000); // Pausa de 0.08 segundos
}

// Mensaje final de éxito en la misma línea o salto limpio
echo "\r\033[K"; // Limpia la línea del spinner
echo "{$verde}✔ ¡Selector de tareas listo!{$reset}\n\n";
    echo "" . PHP_EOL;
    echo "1. Ver tareas" . PHP_EOL;
    echo "2. Agregar tareas" . PHP_EOL;
    echo "3. Volver a Iniciar Sesion" . PHP_EOL;
    echo "0. Cerrar sesion" . PHP_EOL;
    
    $task = (string) readline("Seleccione una tarea para continuar: ");
    limpiarPantalla();
    if (!in_array($task, $validOptions)) {
        echo "opcion no valida" . PHP_EOL;
        echo "ingrese una opcion valida" . PHP_EOL;
    } 

    switch ($task) {
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


    

} while ($task !== "0");
