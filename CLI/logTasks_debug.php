<?php
// Reproduce la misma estructura que logTasks.php pero con trazas
require __DIR__ . '/security/conexion.php';
require_once __DIR__ . '/audio_helper.php';

echo "[1] audio_helper cargado OK\n";
echo "[2] isAudioPlaying antes: " . (isAudioPlaying() ? 'SÍ' : 'NO') . "\n";

$r = startBackgroundAudio(getDefaultAudioFile(), true);
echo "[3] startBackgroundAudio devolvió: " . var_export($r, true) . "\n";
echo "[4] isAudioPlaying después: " . (isAudioPlaying() ? 'SÍ' : 'NO') . "\n";
echo "[5] Escuchando 10s... ¿suena el tono?\n";
sleep(10);
echo "[6] isAudioPlaying tras 10s: " . (isAudioPlaying() ? 'SÍ' : 'NO') . "\n";
stopBackgroundAudio();
echo "[7] Detenido\n";