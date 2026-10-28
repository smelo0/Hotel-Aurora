<?php
require_once __DIR__ . '/../security/auth_guard.php';

$sessionFile = __DIR__ . '/session.json';
if (file_exists($sessionFile)) {
    unlink($sessionFile);
}

$frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
$mensaje = "Cerrando sesión...";

for ($i = 0; $i < 20; $i++) {
    $frame = $frames[$i % count($frames)];
    // El \r permite sobrescribir la línea actual para dar efecto de movimiento
    echo "\r{$cyan}{$frame}{$reset} {$mensaje}";
    usleep(80000); // Pausa de 0.08 segundos
}

// Mensaje final de éxito en la misma línea o salto limpio
echo "\r\033[K"; // Limpia la línea del spinner
echo "{$verde}✔ ¡Sesión cerrada correctamente!{$reset}\n\n";


exit(0);