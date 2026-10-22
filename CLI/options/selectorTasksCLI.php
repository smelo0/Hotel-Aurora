<?php

// seleccionar tarea

do {
    echo "" . PHP_EOL;
    echo "==============Panel seleccion de tareas===============" . PHP_EOL;
    echo "" . PHP_EOL;
    echo "1. Ver tareas" . PHP_EOL;
    echo "2. Agregar tareas" . PHP_EOL;
    $task = (string) readline("Seleccione una tarea para continuar: ");
    
    if ($task !== "1" || "2" || "3") {
        echo "opcion no valida" . PHP_EOL;
        echo "ingrese una opcion valida" . PHP_EOL;
    } else {
        switch ($task) {
        case "1":
            require __DIR__ . "/viewTasks.php";
            break;
        case "2":
            require __DIR__ . "/addTasks.php";
            break;
        case "0":
            echo "Cerrando modulo tareas.";
            break;
        };
    };

    

} while ($task !== "0")


;
