<?php

if (!function_exists('loading')) {
    function loading() {
        $frames  = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $mensaje = "Iniciando módulos del sistema...";
        $cyan    = "\033[36m";
        $reset   = "\033[0m";

        for ($i = 0; $i < 10; $i++) {
            $frame = $frames[$i % count($frames)];
            echo "\r{$cyan}{$frame}{$reset} {$mensaje}";
            usleep(80000);
        }
    }
}