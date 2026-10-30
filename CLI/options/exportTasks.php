<?php
require_once __DIR__ . '/../security/auth_guard.php';
require_once __DIR__ . '/../security/timeOut.php';

use App\Logger;
echo "\n";
loading();
limpiarPantalla();

echo "{$amarillo}
░██████████                                              ░██                        
░██                                                      ░██                        
░██         ░██    ░██ ░████████   ░███████  ░██░████ ░████████  ░██████   ░██░████ 
░█████████   ░██  ░██  ░██    ░██ ░██    ░██ ░███        ░██          ░██  ░███     
░██           ░█████   ░██    ░██ ░██    ░██ ░██         ░██     ░███████  ░██      
░██          ░██  ░██  ░███   ░██ ░██    ░██ ░██         ░██    ░██   ░██  ░██      
░██████████ ░██    ░██ ░██░█████   ░███████  ░██          ░████  ░█████░██ ░██      
                       ░██                                                          
                       ░██{$reset}" . PHP_EOL;
echo "\n";

$validOptions = ["1", "2", "3", "0"];

do {
    echo "1. Exportar a CSV\n";
    echo "2. Exportar a TXT\n";
    echo "3. Agregar nueva tarea.\n";
    echo "4. Ver tareas.\n";
    echo "5. Ver dashboard.\n";
    echo "6. Volver al menu.\n";
    echo "0. Cerrar Sesion\n";


    $exportTasksSelector = trim(readline("Seleccione una tarea para continuar: "));
    // preparamos el la consulta
    $stmt = $conexion->query("SELECT * FROM tarea");
    $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    switch ($exportTasksSelector) {
        case "1":
            if (empty($tareas)) {
                echo "No hay registros en la tabla 'tareas'.\n";
                exit;
            }

            // --- EXPORTAR A CSV (Método manual compatible con cualquier versión de PHP) ---
            $filename = "tareas_export.csv";
            $csvContent = "";

            // Generar cabeceras
            $headers = array_keys($tareas[0]);
            $csvContent .= '"' . implode('","', $headers) . '"' . "\n";

            // Generar filas de datos
            foreach ($tareas as $tarea) {
                $values = array_map(function($val) {
                    // Si el valor es null, lo convertimos a string vacío ''
                    $safeVal = $val ?? '';
                    // Escapar comillas dobles internas si existen
                    return str_replace('"', '""', $safeVal);
                }, array_values($tarea));
                
                $csvContent .= '"' . implode('","', $values) . '"' . "\n";
            }

            file_put_contents($filename, $csvContent);
            echo "¡Éxito! Archivo CSV generado correctamente: $filename\n";
            Logger::registrarLog("INFO", "Archivo CSV generado correctamente: $filename || User ID: $userId || CLI");
            echo "\n";
            break;
        case "2": 
            if (empty($tareas)) {
                echo "No hay registros en la tabla 'tareas'.\n";
                exit;
            };

            // --- EXPORTAR A TXT ---
            $filename = "tareas_export.txt";
            $output = "";

            // Obtener nombres de columnas para formatear una cabecera simple
            $headers = array_keys($tareas[0]);
            $output .= implode("\t | \t", $headers) . "\n";
            $output .= str_repeat("-", 80) . "\n";

            // Agregar filas tabuladas
            foreach ($tareas as $tarea) {
                $output .= implode("\t | \t", array_values($tarea)) . "\n";
            }

            file_put_contents($filename, $output);
            echo "¡Éxito! Archivo TXT generado correctamente: $filename\n";
            Logger::registrarLog("INFO", "Archivo TXT generado correctamente: $filename || User ID: $userId || CLI");
            echo "\n";
            break;
        case "3":
            require __DIR__ . "/addTasks.php";
            exit(2);
        case "4":
            require __DIR__ . "/viewTasks.php";
            exit(2);
        case "5":
            require __DIR__ . "/viewDashboard.php";
            exit(2);
        case "6":
            require __DIR__ . "/selectTasks.php";
            exit(2);
        case "0":
            echo "Cerrando sesion tareas." . PHP_EOL;
            echo "\n";
            require_once __DIR__. '/../security/logout.php';
            exit(0);
    }

} while ($exportTasksSelector !== 0);