<?php
require_once __DIR__ . '/../security/auth_guard.php';

// Definición de colores ANSI estándar
$rojo          = "\033[31m";
$verde         = "\033[32m";
$amarillo      = "\033[33m";
$reset         = "\033[0m";
$verdeOliva    = "\033[38;5;64m";
$doradoOscuro  = "\033[38;5;178m";

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
echo "{$verde}✔ ¡Añade tus tareas!{$reset}\n\n";

echo "\n";
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "{$verdeOliva}=====================Añadir Tareas====================={$reset}" . PHP_EOL;
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "" . PHP_EOL;
