<?php

$sessionFile = __DIR__ . '/../session.json';

$sessionData = json_decode(file_get_contents($sessionFile), true);
$userId = $sessionData['id_usu'] ?? null;
$lastActivity = $sessionData['last_activity'] ?? null;

// 4. VALIDACIÓN DE TIMEOUT POR INACTIVIDAD (5 minutos = 300 segundos)
$tiempo_inactividad = 10; // Pon 10 para probar, luego cámbialo a 300

if (isset($lastActivity) && $lastActivity > 0) {
    $segundos_transcurridos = time() - $lastActivity;

    if ($segundos_transcurridos > $tiempo_inactividad) {
        echo "\n[SESIÓN EXPIRADA] La sesión ha expirado por inactividad.\n";
        @unlink($sessionFile);
        // Usamos die() en lugar de exit para garantizar que muera aquí mismo el proceso CLI
        die(1); 
    }
}


// 7. ACTUALIZAR EL TIMESTAMP DE ÚLTIMA ACTIVIDAD
// Cada vez que ejecute un comando con éxito, el contador se reinicia a 5 minutos.
$sessionData['last_activity'] = time();
file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));
