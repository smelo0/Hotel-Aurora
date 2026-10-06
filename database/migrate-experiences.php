<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
    $arguments = getopt('', ['port::']);
    $port = isset($arguments['port'])
        ? filter_var($arguments['port'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]])
        : filter_var($_ENV['DB_PORT'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    if ($port === false || $port === null) {
        throw new RuntimeException('Configura un puerto MySQL válido o indícalo con --port.');
    }

    $host = (string) ($_ENV['DB_HOST'] ?? '');
    $user = (string) ($_ENV['DB_USER'] ?? '');
    $password = (string) ($_ENV['DB_PASS'] ?? '');
    $database = (string) ($_ENV['DB_NAME'] ?? '');
    if ($host === '' || $user === '' || $database === '') {
        throw new RuntimeException('Faltan parámetros de conexión MySQL requeridos en el entorno.');
    }

    $conexion = mysqli_init();
    if (!$conexion) {
        throw new RuntimeException('No se pudo inicializar la conexión MySQL.');
    }
    $timeout = (int) ($_ENV['DB_TIMEOUT'] ?? 5);
    $conexion->options(MYSQLI_OPT_CONNECT_TIMEOUT, max(1, $timeout));
    $conexion->real_connect($host, $user, $password, $database, (int) $port);
    $conexion->set_charset('utf8mb4');

    (new \App\Database\ExperienceSchemaMigrator($conexion))->migrate();
    fwrite(STDOUT, "Migración de experiencias completada.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Falló la migración de experiencias: {$error->getMessage()}\n");
    exit(1);
}
