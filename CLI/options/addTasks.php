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

$newTasks = [];
// 2. Solicitamos los datos de forma secuencial

// Validamos que el título no esté vacío (campo obligatorio)
do {
    $newTasks['tit_tar'] = trim(readline("1. Título de la tarea: "));
    if (empty($newTasks['tit_tar'])) {
        echo "\033[31mEl título es obligatorio. Intenta de nuevo.\033[0m\n";
    }
} while (empty($newTasks['tit_tar']));

$newTasks['cat_tar']       = trim(readline("2. Categoría (ej. Mantenimiento, Reservas): "));
$newTasks['prioridad_tar'] = trim(readline("3. Prioridad (Alta / Media / Baja): "));
$newTasks['des_tar']       = trim(readline("4. Descripción detallada: "));

// Estado por defecto si el usuario lo deja en blanco
$estadoInput = trim(readline("5. Estado inicial [Default: Pendiente]: "));
$newTasks['est_tar'] = empty($estadoInput) ? 'Pendiente' : $estadoInput;
// 3. Preparamos e insertamos en la base de datos con PDO
echo "\n\033[33mGuardando tarea...\033[0m\n";
try {
    // Los marcadores (:nombre) coinciden intencionalmente con las llaves del array $newTasks
    $sql = "INSERT INTO tarea (tit_tar, cat_tar, prioridad_tar, des_tar, est_tar, cod_usu_tar) 
            VALUES (:tit_tar, :cat_tar, :prioridad_tar, :des_tar, :est_tar, 1)";
    
    $stmt = $conexion->prepare($sql);
    
    // Al pasar $newTasks directamente, PDO asocia las llaves a los marcadores
    $stmt->execute($newTasks);
    echo "\033[32m✔ Tarea registrada exitosamente.\033[0m\n";
} catch (PDOException $e) {
    echo "\033[31m✖ Error al guardar la tarea en la base de datos:\033[0m\n";
    echo $e->getMessage() . "\n";
}
// Pausa para que el usuario lea el resultado antes de volver al menú
echo "\nPresiona ENTER para volver al menú principal...";
 
fgets(STDIN);