<?php
require_once __DIR__ . '/../security/auth_guard.php';
require_once __DIR__ . '/../security/timeOut.php';
// Definición de colores ANSI estándar
use App\Logger;
echo "\n";
loading();

echo $segundos_transcurridos;

// Mensaje final de éxito en la misma línea o salto limpio
echo "\r\033[K"; // Limpia la línea del spinner
echo "{$verde}✔ ¡Añade tus tareas!{$reset}";

echo "{$amarillo}
   ░███                                                                  
  ░██░██                                                                 
 ░██  ░██   ░████████ ░██░████  ░███████   ░████████  ░██████   ░██░████ 
░█████████ ░██    ░██ ░███     ░██    ░██ ░██    ░██       ░██  ░███     
░██    ░██ ░██    ░██ ░██      ░█████████ ░██    ░██  ░███████  ░██      
░██    ░██ ░██   ░███ ░██      ░██        ░██   ░███ ░██   ░██  ░██      
░██    ░██  ░█████░██ ░██       ░███████   ░█████░██  ░█████░██ ░██      
                  ░██                            ░██                     
            ░███████                       ░███████{$reset}" . PHP_EOL;

echo "\n";

$newTasks = [];
// 2. Solicitamos los datos de forma secuencial

// Validamos que el título no esté vacío (campo obligatorio)
do {
    $newTasks['tit_tar'] = trim(readline("1. Título de la tarea: "));
    if (empty($newTasks['tit_tar'])) {
        echo "\033[31mEl título es obligatorio. Intenta de nuevo.\033[0m\n";
    }
} while (empty($newTasks['tit_tar']));

$newTasks['cat_tar'] = trim(readline("2. Categoría (ej. Mantenimiento, Reservas): "));

// validamos prioridad
do {
    $validNewTasks = ["Alta", "Media", "Baja", "alta", "media", "baja", "alta"];
    $newTasks['prioridad_tar'] = trim(readline("3. Prioridad (Alta / Media / Baja): "));
    if (!in_array($newTasks['prioridad_tar'], $validNewTasks)) {
        echo "\033[31mEl la prioridad debe ser Alta, Media o Baja. Intenta de nuevo.\033[0m\n";
    }
} while (!in_array($newTasks['prioridad_tar'], $validNewTasks));

$newTasks['des_tar'] = trim(readline("4. Descripción detallada: "));

// 3. Preparamos e insertamos en la base de datos con PDO
echo "\n\033[33mGuardando tarea...\033[0m\n";
try {
    // Los marcadores (:nombre) coinciden intencionalmente con las llaves del array $newTasks
    $sql = "INSERT INTO tarea (tit_tar, cat_tar, prioridad_tar, des_tar, est_tar, cod_usu_tar) 
            VALUES (:tit_tar, :cat_tar, :prioridad_tar, :des_tar, 'Pendiente', 1)";
    
    $stmt = $conexion->prepare($sql);
    
    // Al pasar $newTasks directamente, PDO asocia las llaves a los marcadores
    $stmt->execute($newTasks);
    echo "\033[32m✔ Tarea agregada exitosamente.\033[0m\n";
    Logger::registrarLog("INFO", "Tarea agregada exitosamente. || User ID: $userId || CLI");
} catch (PDOException $e) {
    echo "\033[31m✖ [Error] al guardar la tarea en la base de datos:{$reset}\n";
    Logger::registrarLog("Error", "[Error] al guardar la tarea en la base de datos: || User ID: $userId || CLI");
    echo $e->getMessage() . "\n";
}

// menu

$validOptions = ["1", "2", "3", "0"];

do {

    echo "\n";
    echo "1. Agregar otra tarea." . PHP_EOL;
    echo "2. Ver tareas" . PHP_EOL;
    echo "3. Exportar tareas. " . PHP_EOL;
    echo "4. Ver dashboard." . PHP_EOL;
    echo "5. Volver al menu." . PHP_EOL;
    echo "0. Cerrar sesion" . PHP_EOL;
    
    $addTasksSelector = (string) readline("Seleccione una tarea para continuar: ");
    limpiarPantalla();
    if (!in_array($viewTasksSelector, $validOptions)) {
        echo "opcion no valida" . PHP_EOL;
        echo "ingrese una opcion valida" . PHP_EOL;
    } 

    switch ($addTasksSelector) {
        case "1":
            require __DIR__ . "/addTasks.php";
            break 2;
        case "2":
            require __DIR__ . "/viewTasks.php";
            break 2;
        case "3":
            require __DIR__ . "/exportTasks.php";
            break 2;
        case "4":
            require __DIR__ . "/viewDashboard.php";
            break 2;
        case "5":
            require __DIR__ . "/selectTasks.php";
            break 2;
        case "0":
            echo "Cerrando sesion tareas." . PHP_EOL;
            require_once __DIR__. '/../security/logout.php';
            exit(0);
    };

} while ($addTasksSelector != 0);
