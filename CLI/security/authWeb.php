<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$sessionFile = __DIR__ . '/../session.json';
$sessionTimeout = 10;
$sessionIsActive = false;

$sessionJson = is_file($sessionFile) ? file_get_contents($sessionFile) : false;
$sessionData = is_string($sessionJson) ? json_decode($sessionJson, true) : null;
$userId = is_array($sessionData)
    ? filter_var($sessionData['id_usu'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;
$lastActivity = is_array($sessionData)
    ? filter_var($sessionData['last_activity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
    : false;

if (
    $userId
    && (string) ($sessionData['cod_rol_usu'] ?? '') === '1'
    && $lastActivity
    && time() >= $lastActivity
    && time() - $lastActivity <= $sessionTimeout
) {
    $sessionIsActive = true;
}

echo json_encode($sessionIsActive);