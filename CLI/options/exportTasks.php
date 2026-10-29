<?php
require_once __DIR__ . '/../security/auth_guard.php';
require_once __DIR__ . '/../security/timeOut.php';


limpiarPantalla();

echo "{$amarillo}
░██████████                                              ░██                          ░██                      
░██                                                      ░██                                                   
░██         ░██    ░██ ░████████   ░███████  ░██░████ ░████████  ░██████    ░███████  ░██ ░███████  ░████████  
░█████████   ░██  ░██  ░██    ░██ ░██    ░██ ░███        ░██          ░██  ░██    ░██ ░██░██    ░██ ░██    ░██ 
░██           ░█████   ░██    ░██ ░██    ░██ ░██         ░██     ░███████  ░██        ░██░██    ░██ ░██    ░██ 
░██          ░██  ░██  ░███   ░██ ░██    ░██ ░██         ░██    ░██   ░██  ░██    ░██ ░██░██    ░██ ░██    ░██ 
░██████████ ░██    ░██ ░██░█████   ░███████  ░██          ░████  ░█████░██  ░███████  ░██ ░███████  ░██    ░██ 
                       ░██                                                                                     
                       ░██{$reset}" . PHP_EOL;
echo "\n";

$validOptions = ["1", "2", "3", "0"];

do {
    echo "En que formato quieres hacer la exportacion?\n";
    echo "1. Exportar a CSV\n";
    echo "2. Exportar a TXT\n";
    echo "3. Agregar nueva tarea.\n";
    echo "4. Ver tareas.\n";
    echo "5. Volver a Iniciar Sesion\n";
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

            $filename = "tareas_export.csv";
            $file = fopen($filename, 'w');

            // Escribir cabeceras basadas en las llaves del primer registro
            fputcsv($file, array_keys($tareas[0]));

            // Escribir filas de datos
            foreach ($tareas as $tarea) {
                fputcsv($file, $tarea);
            }
            fclose($file);

            echo "¡Éxito! Archivo CSV generado correctamente: $filename\n";
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
            break;
        case "3":
            require __DIR__ . "/addTasks.php";
            exit(2);
        case "4":
            require __DIR__ . "/viewTasks.php";
        case "5":
            require __DIR__ . "/../logTasks.php";
            exit(2);
        case "0":
            echo "Cerrando sesion tareas." . PHP_EOL;
            require_once __DIR__. '/../security/logout.php';
            exit(0);
    }

} while ($exportTasksSelector !== 0);