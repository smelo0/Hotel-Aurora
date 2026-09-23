<?php
// $$nivel" puede tener tres clasificaciones: 'INFO', 'WARNING' y 'ERROR'

function registrarLog($nivel, $mensaje, $contexto = []) {
    // 1. Creamos la clasificacion por semanas
    $semanaActual = date('Y') . 'W' . date('D');

    // 2. Definir la ruta del archivo de log
    $archivo = __DIR__ . '/logs/app-' . $semanaActual . '.log';
    
    // Asegurarse de que la carpeta 'logs' exista
    if (!file_exists(__DIR__ . '/logs')) {
        // el permiso 0777 hay que cambiarlo a 0755 en caso de desplegarlo en servidor
        mkdir(__DIR__ . '/logs', 0777, true);
    }
    
    // 3. Crear una estructura limpia (Formato JSON por línea)
    $datosLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level'     => strtoupper($nivel),
        'message'   => $mensaje,
        'context'   => $contexto,
        'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
    ];
    
    // Convertir a JSON y agregar un salto de línea
    $linea = json_encode($datosLog, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    
    // 4. Escribir en el archivo de forma segura (FILE_APPEND evita sobrescribir)
    file_put_contents($archivo, $linea, FILE_APPEND);
}