<?php
/**
 * audio_helper.php — v3
 * Audio de fondo para CLI.
 *
 * Windows: lanza PowerShell vía wscript.exe + VBS para que el proceso hijo
 *          quede TOTALMENTE desacoplado del PHP padre. Sobrevive aunque
 *          PHP siga corriendo (readline, login, etc.).
 * Unix   : ffplay con marker en argv[0] para matar con pkill -f.
 *
 * Log de fallas: %TEMP%\hotel_aurora_audio.log
 */

if (!defined('AUDIO_MARKER')) {
    define('AUDIO_MARKER', 'HOTEL_AURORA_BG_AUDIO');
}

if (!function_exists('audioLogPath')) {
    function audioLogPath(): string {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
             . DIRECTORY_SEPARATOR . 'hotel_aurora_audio.log';
    }
}

if (!function_exists('getDefaultAudioFile')) {
    function getDefaultAudioFile(): string {
        return __DIR__ . '/.wav/sonido.wav';
    }
}

if (!function_exists('runPowerShell')) {
    /** Ejecuta PowerShell (síncrono). Usado solo para consultar/matar procesos. */
    function runPowerShell(string $script): string {
        $utf16 = mb_convert_encoding($script, 'UTF-16LE', 'UTF-8');
        $b64   = base64_encode($utf16);
        $out   = shell_exec('powershell -NoProfile -NonInteractive '
                          . '-ExecutionPolicy Bypass -EncodedCommand ' . $b64 . ' 2>nul');
        return $out ?? '';
    }
}

if (!function_exists('isAudioPlaying')) {
    function isAudioPlaying(): bool {
        if (PHP_OS_FAMILY === 'Windows') {
            $script = 'if (Get-CimInstance Win32_Process -Filter "Name=\'powershell.exe\'" '
                    . '| Where-Object { $_.CommandLine -like \'*' . AUDIO_MARKER . '*\' }) '
                    . '{ Write-Output "PLAYING" }';
            return strpos(runPowerShell($script), 'PLAYING') !== false;
        }
        $out = trim((string) shell_exec('pgrep -f ' . escapeshellarg(AUDIO_MARKER) . ' 2>/dev/null'));
        return $out !== '';
    }
}

if (!function_exists('startBackgroundAudio')) {
    function startBackgroundAudio(?string $file = null, bool $debug = false): bool {
        $file = $file ?? getDefaultAudioFile();

        if (!file_exists($file)) {
            @file_put_contents(audioLogPath(),
                '[' . date('Y-m-d H:i:s') . "] Archivo no encontrado: $file\n", FILE_APPEND);
            if ($debug) echo "[audio] Archivo no encontrado: $file\n";
            return false;
        }
        if (isAudioPlaying()) {
            if ($debug) echo "[audio] Ya estaba reproduciéndose.\n";
            return true;
        }

        $absolutePath = realpath($file);

        if (PHP_OS_FAMILY === 'Windows') {
            $psFile  = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '\hotel_aurora_audio.ps1';
            $vbsFile = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '\hotel_aurora_audio.vbs';
            $logPath = audioLogPath();

            $safePath = str_replace("'", "''", $absolutePath);
            $safeLog  = str_replace("'", "''", $logPath);

                        $ps = <<<PS
\$ErrorActionPreference = 'Stop'
\$path = '$safePath'
\$log  = '$safeLog'

try {
    \$sp = New-Object System.Media.SoundPlayer(\$path)
    \$sp.Load()
    \$sp.PlayLooping()
    "[$(Get-Date -Format o)] SoundPlayer OK pid=\$PID" | Out-File -FilePath \$log -Append -Encoding utf8
    Start-Sleep -Seconds 86400
} catch {
    "[$(Get-Date -Format o)] SoundPlayer FALLO: $(\$_.Exception.Message)" | Out-File -FilePath \$log -Append -Encoding utf8
}
PS;      file_put_contents($psFile, $ps);

            // VBS: lanza PowerShell oculto (0) y sin esperar (False) => desacoplado total
            $psFileEsc = str_replace('"', '""', $psFile);
            $vbs = 'CreateObject("WScript.Shell").Run "powershell -NoProfile -STA '
                 . '-ExecutionPolicy Bypass -File ""' . $psFileEsc . '"" ' . AUDIO_MARKER . '", 0, False';
            file_put_contents($vbsFile, $vbs);

            // wscript.exe es un proceso de Windows que devuelve el control inmediatamente
            pclose(popen('wscript.exe "' . $vbsFile . '"', 'r'));

            if ($debug) echo "[audio] Lanzado vía wscript (Windows): $absolutePath\n";
            return true;
        }

        // Linux / macOS
        $wrapped = 'bash -c "exec -a ' . escapeshellarg(AUDIO_MARKER)
                 . ' ffplay -nodisp -loop 0 -loglevel quiet ' . escapeshellarg($file) . '"'
                 . ' > /dev/null 2>&1 &';
        shell_exec($wrapped);

        if ($debug) echo "[audio] Lanzado (Unix): $absolutePath\n";
        return true;
    }
}

if (!function_exists('stopBackgroundAudio')) {
    function stopBackgroundAudio(): void {
        if (PHP_OS_FAMILY === 'Windows') {
            $script = 'Get-CimInstance Win32_Process -Filter "Name=\'powershell.exe\'" '
                    . '| Where-Object { $_.CommandLine -like \'*' . AUDIO_MARKER . '*\' } '
                    . '| ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }';
            runPowerShell($script);
            return;
        }
        shell_exec('pkill -f ' . escapeshellarg(AUDIO_MARKER) . ' 2>/dev/null');
    }
}

if (!function_exists('toggleBackgroundAudio')) {
    function toggleBackgroundAudio(?string $file = null): bool {
        if (isAudioPlaying()) {
            stopBackgroundAudio();
            return false;
        }
        startBackgroundAudio($file);
        return true;
    }
}