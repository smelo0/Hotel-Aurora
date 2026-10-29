<?php

require __DIR__ . '/audio_helper.php';

$audioFile = getDefaultAudioFile();

if (!file_exists($audioFile)) {
    die("Error: El archivo de audio '$audioFile' no se encuentra en la carpeta.\n");
}

echo "=== Reproductor de Sonido de Fondo (CLI) ===\n";
echo "Presiona [m] + [Enter] para apagar / encender el sonido.\n";
echo "Presiona [q] + [Enter] para salir.\n\n";

// Arrancar la música de fondo al iniciar (debug = true)
startBackgroundAudio($audioFile, true);
echo "Estado actual: [🔊 SONIDO DE FONDO REPRODUCIENDO]\n\n> ";

stream_set_blocking(STDIN, false);

while (true) {
    $input = trim((string) fgets(STDIN));

    if ($input !== '') {
        $input = strtolower($input);

        if ($input === 'q') {
            stopBackgroundAudio();
            echo "Saliendo...\n";
            break;
        }

        if ($input === 'm') {
            $playing = toggleBackgroundAudio($audioFile);
            echo $playing
                ? "Estado actual: [🔊 SONIDO REACTIVADO]\n"
                : "Estado actual: [🔇 SONIDO SILENCIADO]\n";
        }
        echo "> ";
    }

    usleep(100000);
}