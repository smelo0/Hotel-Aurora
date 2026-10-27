<?php
require_once __DIR__ . '/../security/auth_guard.php';
limpiarPantalla();

$frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
$mensaje = "cargando tareas...";

for ($i = 0; $i < 20; $i++) {
    $frame = $frames[$i % count($frames)];
    // El \r permite sobrescribir la línea actual para dar efecto de movimiento
    echo "\r{$cyan}{$frame}{$reset} {$mensaje}";
    usleep(80000); // Pausa de 0.08 segundos
}

// Mensaje final de éxito en la misma línea o salto limpio
echo "\r\033[K"; // Limpia la línea del spinner
echo "{$verde}✔ ¡Tareas cargadas correctamente!{$reset}\n\n"; 

echo "\n";
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "{$verdeOliva}=======================Ver Tareas========================{$reset}" . PHP_EOL;
echo "{$amarillo}*********************************************************{$reset}" . PHP_EOL;
echo "" . PHP_EOL;

$headers = ["cod_tar",  "tit_tar", "des_tar", "cat_tar", "est_tar", "fec_tar"
];
$rows = [
    ["1", "limpiar","Limpiar el polvo del techo", "Verificar stock de productos en almacén", "completada", "Pendiente"],
    ["2", "barrer","Barrer las escaleras", "Realizar backup y actualizar registros", "completada", "En Progreso"],
    ["3", "enviar reportes", "Enviar reportes semanales al equipo de ventas", "completada", "Completado"],
    ["4", "trapear","Trapea los baños", "Coordinar reunión con clientes potenciales", "completada", "Pendiente"],
    ["5", "surtir", "Llevar papel higienico a los baños", "Actualizar software y realizar pruebas de seguridad", "completada", "En Progreso"]
];