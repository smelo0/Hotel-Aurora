<?php
// $$nivel" puede tener tres clasificaciones: 'INFO', 'WARNING' y 'ERROR'

namespace App;

class Logger {
    public static function registrarLog($nivel, $mensaje, $contexto = []) {

        date_default_timezone_set('America/Bogota');
        $semanaActual = date('Y') . '-W' . date('W');
        $dirLogs = __DIR__ . '/../logs';
        $archivo = $dirLogs . '/app-' . $semanaActual . '.log';

        try {
            if (!file_exists($dirLogs)) {
                mkdir($dirLogs, 0755, true);
            }

            // Obtención robusta de la IP
            $ip = 'CLI';
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }
            }

            $datosLog = [
                'timestamp' => date('Y-m-d H:i:s'),
                'level'     => strtoupper($nivel),
                'message'   => $mensaje,
                'context'   => $contexto,
                'ip'        => $ip
            ];

            $linea = json_encode($datosLog, JSON_UNESCAPED_UNICODE) . PHP_EOL;

            // FILE_APPEND con LOCK_EX para seguridad en concurrencia
            @file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);
            
        } catch (\Throwable $e) {
            // Fallback opcional por si el log falla (ej. escribir en el error_log del servidor)
            error_log("Error al registrar log: " . $e->getMessage());
        }
    }
}