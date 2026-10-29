<?php

// Ruta correcta apuntando a la subcarpeta .wav
$audioFile = __DIR__ . "/.wav/sonido.wav"; 

if (!file_exists($audioFile)) {
    die("Error: El archivo de audio '$audioFile' no se encuentra en la carpeta.\n");
}

echo "=== Reproductor de Sonido de Fondo (CLI) ===\n";
echo "Presiona [m] + [Enter] para apagar / encender el sonido.\n";
echo "Presiona [q] + [Enter] para salir.\n\n";

$isMuted = false;

// Función para iniciar la reproducción en bucle usando SoundPlayer (nativo de Windows para .wav)
function startBackgroundAudio($file) {
    if (!file_exists($file)) return;
    $absolutePath = realpath($file);
    $os = PHP_OS_FAMILY;
    
    if ($os === 'Windows') {
        // Usa .NET SoundPlayer que está diseñado específicamente para archivos .wav con bucle continuo
        $psCommand = "(New-Object System.Media.SoundPlayer('$absolutePath')).PlayLooping(); Start-Sleep -Seconds 86400;";
        pclose(popen("start /b powershell -WindowStyle Hidden -Command \"$psCommand\" >nul 2>&1", "r"));
    } else {
        // Compatible con Linux/Mac si se llega a usar ffplay
        $cmd = "ffplay -nodisp -loop 0 -loglevel quiet " . escapeshellarg($file) . " > /dev/null 2>&1 &";
        system($cmd);
    }
}

// Función para detener el audio cerrando el proceso de PowerShell
function stopBackgroundAudio() {
    $os = PHP_OS_FAMILY;
    if ($os === 'Windows') {
        system("taskkill /f /im powershell.exe >nul 2>&1");
    } else {
        system("pkill -f ffplay > /dev/null 2>&1");
    }
}

// Arrancar la música de fondo al iniciar
startBackgroundAudio($audioFile);
echo "Estado actual: [🔊 SONIDO DE FONDO REPRODUCIENDO]\n\n> ";

stream_set_blocking(STDIN, false);

while (true) {
    $input = trim(fgets(STDIN));
    
    if ($input !== "") {
        $input = strtolower($input);
        
        if ($input === 'q') {
            stopBackgroundAudio();
            echo "Saliendo...\n";
            break;
        }
        
        if ($input === 'm') {
            $isMuted = !$isMuted;
            if ($isMuted) {
                stopBackgroundAudio();
                echo "Estado actual: [🔇 SONIDO SILENCIADO]\n";
            } else {
                startBackgroundAudio($audioFile);
                echo "Estado actual: [🔊 SONIDO REACTIVADO]\n";
            }
        }
        echo "> ";
    }
    
    usleep(100000); // Pausa de 100ms para optimizar el uso de la CPU
}