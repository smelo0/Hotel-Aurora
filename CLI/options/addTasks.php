<?php
require_once __DIR__ . '/../security/auth_guard.php';

// Definición de colores ANSI estándar

loading();

// Mensaje final de éxito en la misma línea o salto limpio
echo "\r\033[K"; // Limpia la línea del spinner
echo "{$verde}✔ ¡Añade tus tareas!{$reset}\n\n";

echo "\n";
echo "{$amarillo}
                                                                               ░██                                                         
                                                                               ░██                                                         
 ░██████    ░████████ ░██░████  ░███████   ░████████  ░██████   ░██░████    ░████████  ░██████   ░██░████  ░███████   ░██████    ░███████  
      ░██  ░██    ░██ ░███     ░██    ░██ ░██    ░██       ░██  ░███           ░██          ░██  ░███     ░██    ░██       ░██  ░██        
 ░███████  ░██    ░██ ░██      ░█████████ ░██    ░██  ░███████  ░██            ░██     ░███████  ░██      ░█████████  ░███████   ░███████  
░██   ░██  ░██   ░███ ░██      ░██        ░██   ░███ ░██   ░██  ░██            ░██    ░██   ░██  ░██      ░██        ░██   ░██         ░██ 
 ░█████░██  ░█████░██ ░██       ░███████   ░█████░██  ░█████░██ ░██             ░████  ░█████░██ ░██       ░███████   ░█████░██  ░███████  
                  ░██                            ░██                                                                                       
            ░███████                       ░███████{$reset}" . PHP_EOL;

echo "\n";

do {

    $newTask[''] =  readline("Ingrese el nombre de su tarea: ");
    if (!empty($nameTask)) {
        
    }


} while ($newTask !== 0);