<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Logger;

// Intentamos registrar un mensaje de prueba
Logger::registrarLog('INFO', 'Prueba manual del sistema de logs', [
    'usuario_prueba' => 'Santiago',
    'estado' => 'Verificando funcionamiento'
]);

echo "¡Log de prueba enviado con éxito! Revisa la carpeta logs/";