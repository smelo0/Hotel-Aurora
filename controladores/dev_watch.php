<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$remoteAddr = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isLocal = in_array($remoteAddr, ['127.0.0.1', '::1'], true)
    || str_starts_with($host, 'localhost')
    || str_starts_with($host, '127.0.0.1');

if (!$isLocal) {
    http_response_code(403);
    echo json_encode(['enabled' => false]);
    exit;
}

$rootDir = dirname(__DIR__);
$cssFiles = glob($rootDir . '/assets/css/*.css') ?: [];
$codeFiles = array_merge(
    glob($rootDir . '/*.php') ?: [],
    glob($rootDir . '/includes/*.php') ?: [],
    glob($rootDir . '/assets/js/*.js') ?: []
);

clearstatcache();

$cssState = [];
foreach ($cssFiles as $file) {
    $cssState[basename($file)] = @filemtime($file) ?: 0;
}

$codeState = [];
foreach ($codeFiles as $file) {
    $codeState[basename($file)] = @filemtime($file) ?: 0;
}

echo json_encode([
    'enabled' => true,
    'cssHash' => md5((string) json_encode($cssState)),
    'codeHash' => md5((string) json_encode($codeState)),
    'timestamp' => time(),
], JSON_UNESCAPED_UNICODE);
