<?php
require_once __DIR__ . '/../security/auth_guard.php';

$sessionFile = __DIR__ . '/session.json';
if (file_exists($sessionFile)) {
    unlink($sessionFile);
}
echo "Sesión cerrada correctamente.\n";
exit(0);