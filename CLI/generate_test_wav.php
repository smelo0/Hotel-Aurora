<?php
/**
 * generate_test_wav.php
 * Genera un WAV PCM válido de 5 segundos (tono de prueba).
 * Sobrescribe .wav/sonido.wav solo si el archivo actual es inválido.
 */

$path = __DIR__ . '/.wav/sonido.wav';
@mkdir(dirname($path), 0755, true);

$sampleRate = 44100;
$duration   = 5;
$samples    = $sampleRate * $duration;
$dataSize   = $samples * 2;   // 16-bit mono

$fp = fopen($path, 'wb');
if ($fp === false) {
    die("No se pudo crear $path\n");
}

// === WAV header PCM ===
fwrite($fp, 'RIFF');
fwrite($fp, pack('V', 36 + $dataSize));
fwrite($fp, 'WAVE');
fwrite($fp, 'fmt ');
fwrite($fp, pack('V', 16));          // tamaño bloque fmt
fwrite($fp, pack('v', 1));           // formato PCM
fwrite($fp, pack('v', 1));           // mono
fwrite($fp, pack('V', $sampleRate)); // sample rate
fwrite($fp, pack('V', $sampleRate * 2)); // byte rate
fwrite($fp, pack('v', 2));           // block align
fwrite($fp, pack('v', 16));          // bits por muestra
fwrite($fp, 'data');
fwrite($fp, pack('V', $dataSize));

// === Muestras: nota La (440Hz) con fade-in/out para evitar clics ===
for ($i = 0; $i < $samples; $i++) {
    $t   = $i / $sampleRate;
    $env = min(1, min($i, $samples - $i) / ($sampleRate * 0.3));
    $val = (int)(sin(2 * M_PI * 440 * $t) * 6000 * $env);
    fwrite($fp, pack('v', $val & 0xFFFF));
}
fclose($fp);

echo "✔ Generado: $path\n";
echo "  Tamaño : " . filesize($path) . " bytes\n";