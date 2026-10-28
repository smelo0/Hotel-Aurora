<?php

if (!function_exists('limpiarPantalla')) {
    function limpiarPantalla() {
        // Secuencia ANSI para limpiar la pantalla de la consola
        echo "\033[H\033[J";
    }
}