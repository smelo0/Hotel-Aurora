<?php

if (!function_exists('loading')) {
    function loading() {
        $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $mensaje = "Iniciando módulos del sistema...";
        $cyan    = "\033[36m";
        $reset   = "\033[0m";

        for ($i = 0; $i < 20; $i++) {
            $frame = $frames[$i % count($frames)];
            // El \r permite sobrescribir la línea actual para dar efecto de movimiento
            echo "\r{$cyan}{$frame}{$reset} {$mensaje}";
            usleep(80000); // Pausa de 0.08 segundos
        }
    }
}