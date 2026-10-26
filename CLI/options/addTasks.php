<?php
require_once __DIR__ . '/../security/auth_guard.php';

// Definición de colores ANSI estándar
$rojo          = "\033[31m";
$verde         = "\033[32m";
$amarillo      = "\033[33m";
$reset         = "\033[0m";
$verdeOliva    = "\033[38;5;64m";
$doradoOscuro  = "\033[38;5;178m";

echo "\n";
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "{$verdeOliva}=====================Añadir Tareas====================={$reset}" . PHP_EOL;
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "" . PHP_EOL;