<?php
/**
 * check_wav.php
 * Uso: php check_wav.php .wav\sonido.wav
 * Verifica que un archivo WAV sea PCM válido para System.Media.SoundPlayer.
 */

$file = $argv[1] ?? __DIR__ . '/.wav/sonido.wav';

if (!file_exists($file)) {
    die("❌ No existe: $file\n");
}

$size = filesize($file);
if ($size < 44) {
    die("❌ Archivo demasiado pequeño ($size bytes) — no es un WAV válido.\n");
}

$h = file_get_contents($file, false, null, 0, 44);

$riff = substr($h, 0, 4);
$wave = substr($h, 8, 4);
$fmt  = unpack('v', substr($h, 20, 2))[1] ?? -1;
$ch   = unpack('v', substr($h, 22, 2))[1] ?? 0;
$sr   = unpack('V', substr($h, 24, 4))[1] ?? 0;
$bits = unpack('v', substr($h, 34, 2))[1] ?? 0;

echo "Archivo    : $file\n";
echo "Tamaño     : $size bytes\n";
echo "RIFF       : $riff " . ($riff === 'RIFF' ? '✅' : '❌') . "\n";
echo "WAVE       : $wave " . ($wave === 'WAVE' ? '✅' : '❌') . "\n";
echo "Formato    : $fmt " . ($fmt === 1 ? '(PCM ✅)' : '(NO PCM ❌ — convertir con ffmpeg)') . "\n";
echo "Canales    : $ch " . ($ch === 1 ? '(mono)' : ($ch === 2 ? '(stereo)' : '(?)')) . "\n";
echo "Sample rate: $sr Hz\n";
echo "Bits       : $bits\n";

if ($fmt === 1 && $riff === 'RIFF' && $wave === 'WAVE') {
    echo "\n✅ VÁLIDO para SoundPlayer\n";
    exit(0);
}
echo "\n❌ NO VÁLIDO para SoundPlayer\n";
echo "Convierte con:\n";
$filePcm = str_replace('.wav', '_pcm.wav', $file);
echo "  ffmpeg -i \"$file\" -acodec pcm_s16le -ar 44100 -ac 2 \"$filePcm\"\n";
exit(1);