<?php
// Limpia cualquier salida previa por espacios o includes con espacios en blanco

// controladores/obtener_logs.php
require_once __DIR__ . '/../includes/sesion_seguridad.php';

header('Content-Type: application/json; charset=utf-8');

// Medida de seguridad: Solo Administradores (rol 1 o 2) pueden ver los logs
$rol = (int) ($_SESSION['emp_auth']['rol_usuario'] ?? 0);
if (!in_array($rol, [1, 2], true)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'mensaje' => 'No tienes permisos para ver los logs']);
    exit();
}

$dirLogs = __DIR__ . '/../logs';
$logsData = [];

if (is_dir($dirLogs)) {
    // Buscar todos los archivos que empiecen con app- y terminen en .log
    $archivos = glob($dirLogs . '/app-*.log');
    
    // Leer los archivos (opcionalmente podrías limitar esto para no saturar la memoria si hay meses de logs)
    foreach ($archivos as $archivo) {
        $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lineas as $linea) {
            $decodificado = json_decode($linea, true);
            if (is_array($decodificado)) {
                $logsData[] = $decodificado;
            }
        }
    }
}

// Ordenar todos los logs desde el más reciente al más antiguo
usort($logsData, function($a, $b) {
    return strtotime($b['timestamp']) - strtotime($a['timestamp']);
});

// Limitar a los últimos 500 registros para no sobrecargar el navegador
$logsData = array_slice($logsData, 0, 500);

echo json_encode([
    'status' => 'exito',
    'total' => count($logsData),
    'data' => $logsData
]);
?>