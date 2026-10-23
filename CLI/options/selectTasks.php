<?php 
// seleccionar tarea
require_once __DIR__ . '/../security/auth_guard.php';

$validOptions = ["1", "2", "3", "0"];

do {
    echo "\n";
    echo "*********************************************************" . PHP_EOL;
    echo "===================Selector de tareas====================" . PHP_EOL;
    echo "*********************************************************" . PHP_EOL;
    echo "" . PHP_EOL;
    echo "1. Ver tareas" . PHP_EOL;
    echo "2. Agregar tareas" . PHP_EOL;
    echo "3. Volver a Iniciar Sesion" . PHP_EOL;
    echo "0. Cerrar menu" . PHP_EOL;
    $task = (string) readline("Seleccione una tarea para continuar: ");
    
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
            echo "Cerrando modulo tareas.";
            require_once __DIR__. '/../security/logout.php';
            break;
    };


    

} while ($task !== "0");
