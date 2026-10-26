<?php

function bannerExito(string $mensaje): void {
    // \033[32m = Verde, \033[0m = Reset
    echo "\033[32m✔ {$mensaje}\033[0m\n";
}

function bannerError(string $mensaje): void {
    // \033[31m = Rojo
    echo "\033[31m✖ {$mensaje}\033[0m\n";
}