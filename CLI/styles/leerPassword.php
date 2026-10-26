<?php

function leerPasswordPrompt(string $prompt = "Ingrese su contraseña: "): string {
    echo $prompt;

    // Detectamos si el entorno es Windows (CMD o PowerShell)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Invocamos PowerShell para leer la entrada de forma oculta y segura
        $cmd = 'powershell -Command "$p = Read-Host -AsSecureString; [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))"';
        $password = trim((string) shell_exec($cmd));
        echo PHP_EOL;
        return $password;
    }

    // Para entornos Unix / Linux / macOS / Git Bash
    system('stty -echo');
    $password = trim((string) fgets(STDIN));
    system('stty echo');
    echo PHP_EOL;

    return $password;
}