<?php
require_once __DIR__ . '/../security/auth_guard.php';

$sessionFile = __DIR__ . '/session.json';
if (file_exists($sessionFile)) {
    unlink($sessionFile);
}
echo "{$verde}Sesión cerrada correctamente.\n{$reset}";
exit(0);