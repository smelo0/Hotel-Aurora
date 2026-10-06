<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';

$temporaryDefaultsFile = null;
try {
    \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();
    $arguments = getopt('', ['backup:', 'port::', 'mysqldump::']);
    $backupPath = $arguments['backup'] ?? '';
    if (!is_string($backupPath) || $backupPath === '') {
        throw new RuntimeException('Indica una ruta externa para el respaldo usando --backup=RUTA.');
    }
    $backupDirectory = dirname($backupPath);
    if (!is_dir($backupDirectory) && !mkdir($backupDirectory, 0700, true) && !is_dir($backupDirectory)) {
        throw new RuntimeException('No se pudo crear la carpeta para el respaldo.');
    }
    $resolvedBackupDirectory = realpath($backupDirectory);
    if ($resolvedBackupDirectory === false) {
        throw new RuntimeException('No se pudo resolver la carpeta para el respaldo.');
    }
    $backupPath = $resolvedBackupDirectory . DIRECTORY_SEPARATOR . basename($backupPath);
    if (file_exists($backupPath)) {
        throw new RuntimeException('El archivo de respaldo ya existe; elige una ruta nueva para evitar sobrescribir datos.');
    }

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

    $dumpExecutable = $arguments['mysqldump']
        ?? dirname(PHP_BINARY) . '/../mysql/bin/mysqldump.exe';
    if (!is_string($dumpExecutable) || !is_file($dumpExecutable)) {
        throw new RuntimeException('No se encontró mysqldump; indícalo con --mysqldump=RUTA.');
    }

    $temporaryDefaultsFile = tempnam(sys_get_temp_dir(), 'hotel-db-');
    if ($temporaryDefaultsFile === false) {
        throw new RuntimeException('No se pudo crear la configuración temporal del respaldo.');
    }
    $defaults = "[client]\n"
        . 'host="' . addcslashes($host, "\\\"") . '"' . "\n"
        . 'user="' . addcslashes($user, "\\\"") . '"' . "\n"
        . 'password="' . addcslashes($password, "\\\"") . '"' . "\n"
        . 'port=' . (int) $port . "\n";
    if (file_put_contents($temporaryDefaultsFile, $defaults, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo escribir la configuración temporal del respaldo.');
    }

    $output = fopen($backupPath, 'xb');
    if ($output === false) {
        throw new RuntimeException('No se pudo crear el archivo de respaldo.');
    }
    $process = proc_open(
        [
            $dumpExecutable,
            '--defaults-extra-file=' . $temporaryDefaultsFile,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--events',
            '--hex-blob',
            '--databases',
            $database,
        ],
        [
            0 => ['pipe', 'r'],
            1 => $output,
            2 => ['pipe', 'w'],
        ],
        $pipes
    );
    if (!is_resource($process)) {
        fclose($output);
        throw new RuntimeException('No se pudo iniciar mysqldump.');
    }
    fclose($pipes[0]);
    $dumpError = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $dumpExitCode = proc_close($process);
    fclose($output);
    if ($dumpExitCode !== 0 || !is_file($backupPath) || filesize($backupPath) === 0) {
        @unlink($backupPath);
        throw new RuntimeException('Falló el respaldo MySQL; no se ejecutó la migración. ' . trim((string) $dumpError));
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conexion = mysqli_init();
    if (!$conexion) {
        throw new RuntimeException('No se pudo inicializar la conexión MySQL.');
    }
    $conexion->options(MYSQLI_OPT_CONNECT_TIMEOUT, max(1, (int) ($_ENV['DB_TIMEOUT'] ?? 5)));
    $conexion->real_connect($host, $user, $password, $database, (int) $port);
    $conexion->set_charset('utf8mb4');

    (new \App\Database\ExperienceSchemaMigrator($conexion))->migrate();
    fwrite(STDOUT, "Respaldo verificado y migración completada: {$backupPath}\n");
} catch (Throwable $error) {
    fwrite(STDERR, "No se completó la operación: {$error->getMessage()}\n");
    exit(1);
} finally {
    if (is_string($temporaryDefaultsFile) && is_file($temporaryDefaultsFile)) {
        @unlink($temporaryDefaultsFile);
    }
}
