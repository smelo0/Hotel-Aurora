<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../configuracion/conexion.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    (new \App\Database\ExperienceSchemaMigrator($conexion))->migrate();
    (new \App\Database\PasswordResetSchemaMigrator($conexion))->migrate();
    (new \App\Database\RoomCatalogSchemaMigrator($conexion))->migrate();
    \App\RoomService\RoomServiceSchemaMigrator::asegurar($conexion);
    \App\Billing\BillingRegistrar::asegurarEsquema($conexion);
    fwrite(STDOUT, "Migraciones completadas.\n");
} catch (Throwable $error) {
    fwrite(STDERR, "Fallaron las migraciones: {$error->getMessage()}\n");
    exit(1);
} finally {
    $conexion->close();
}
