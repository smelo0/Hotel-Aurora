<?php
require_once __DIR__ . '/../security/auth_guard.php';
require_once __DIR__ . '/../security/conexion.php';
require_once __DIR__ . '/../security/timeOut.php';
limpiarPantalla();

loading();

// Mensaje final de éxito
echo "\r\033[K";
echo "{$verde}✔ ¡Tareas cargadas correctamente desde la base de datos!{$reset}\n\n"; 

// 1. Consultar los datos reales usando tu conexión existente ($conexion)
$stmt = $conexion->query("SELECT cod_tar , 	tit_tar, cat_tar, prioridad_tar, des_tar, est_tar, fec_tar FROM tarea");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n";
echo "{$amarillo}*******************************************************************************************************{$reset}" . PHP_EOL;
echo "{$verdeOliva}===================================================Tareas============================================{$reset}" . PHP_EOL;
echo "{$amarillo}*******************************************************************************************************{$reset}" . PHP_EOL;
echo "" . PHP_EOL;

// Definición limpia de columnas (7 elementos)
$headers = ["codigo", "titulo", "categoria", "prioridad", "descripcion", "estado", "fecha"];

// Si no hay tareas registradas, evitamos errores
if (empty($rows)) {
    echo "{$amarillo}No hay tareas registradas en la base de datos.{$reset}\n\n";
    exit;
}

// 1. Calcular el ancho máximo necesario para cada columna
$widths = [];
foreach ($headers as $index => $header) {
    $widths[$index] = mb_strwidth($header);
}

foreach ($rows as $row) {
    $index = 0;
    foreach ($row as $cell) {
        $widths[$index] = max($widths[$index], mb_strwidth((string)$cell));
        $index++;
    }
}

// 2. Función auxiliar para imprimir las líneas divisorias horizontales
$printSeparator = function() use ($widths, $verdeOliva, $reset) {
    echo $verdeOliva . "+";
    foreach ($widths as $w) {
        echo str_repeat("-", $w + 2) . "+";
    }
    echo $reset . "\n";
};

// 3. Imprimir la cabecera de la tabla
$printSeparator();
echo $amarillo . "|";
foreach ($headers as $index => $header) {
    echo " " . str_pad($header, $widths[$index], " ", STR_PAD_RIGHT) . " {$amarillo}|";
}
echo $reset . "\n";
$printSeparator();

// 4. Imprimir los datos de las filas obtenidos de MySQL
foreach ($rows as $row) {
    echo "{$verdeOliva}|{$reset}";
    $i = 0;
    foreach ($row as $cell) {
        echo " " . str_pad((string)$cell, $widths[$i], " ", STR_PAD_RIGHT) . " {$verdeOliva}|{$reset}";
        $i++;
    }
    echo "\n";
}

// 5. Cerrar la tabla con la línea inferior
$printSeparator();
echo "\n";