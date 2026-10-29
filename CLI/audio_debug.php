<?php
require __DIR__ . '/audio_helper.php';

echo "=== DEBUG DE AUDIO ===\n";
echo "PHP        : " . PHP_VERSION . " (" . PHP_OS_FAMILY . ")\n";
echo "Archivo    : " . getDefaultAudioFile() . "\n";
echo "Existe     : " . (file_exists(getDefaultAudioFile()) ? 'SÍ (' . filesize(getDefaultAudioFile()) . ' bytes)' : 'NO') . "\n";
echo "Log        : " . audioLogPath() . "\n\n";

// Limpia log previo
@unlink(audioLogPath());

echo "Estado previo: " . (isAudioPlaying() ? 'REPRODUCIENDO' : 'DETENIDO') . "\n\n";

echo ">> Lanzando startBackgroundAudio()...\n";
startBackgroundAudio(null, true);

echo ">> Esperando 3s...\n";
sleep(3);

echo ">> ¿Proceso detectado? " . (isAudioPlaying() ? 'SÍ' : 'NO') . "\n\n";

echo "--- Log de PowerShell ---\n";
echo file_exists(audioLogPath()) ? file_get_contents(audioLogPath()) : "(vacío / no se creó)\n";

echo "\n>> ¿Escuchaste el tono? Ctrl+C para cortar, o espera 10s.\n";
sleep(10);

echo "\n>> Deteniendo...\n";
stopBackgroundAudio();
sleep(1);
echo ">> Estado final: " . (isAudioPlaying() ? 'AÚN SONANDO' : 'DETENIDO') . "\n";