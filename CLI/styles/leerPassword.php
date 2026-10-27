<?php

if (!function_exists('leerPasswordPrompt')) {
    function leerPasswordPrompt(string $prompt = "Ingrese su contraseña: "): string {
        echo $prompt;

        // Detectamos si el entorno es Windows (CMD o PowerShell)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $cmd = 'powershell -Command "$p = Read-Host -AsSecureString; [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))"';
            $password = trim((string) shell_exec($cmd));
            echo PHP_EOL;
            return $password;
        }

        // Para entornos Unix / Linux / macOS
        if (function_exists('system')) {
            system('stty -echo');
            $password = trim((string) fgets(STDIN));
            system('stty echo');
        } else {
            $password = trim((string) fgets(STDIN));
        }
        
        echo PHP_EOL;
        return $password;
    }
}