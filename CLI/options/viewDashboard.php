<?php
// Dashboard del administrador en el CLI (mismos datos que interfaz/admin/secciones_ad/dashboard.php)
require_once __DIR__ . '/../security/auth_guard.php';
require_once __DIR__ . '/../security/conexion.php';
require_once __DIR__ . '/../security/timeOut.php';

// Colores (por si este archivo se ejecuta fuera de logTasks.php)
$rojo         = $rojo         ?? "\033[31m";
$verde        = $verde        ?? "\033[32m";
$amarillo     = $amarillo     ?? "\033[33m";
$azul         = $azul         ?? "\033[34m";
$cyan         = $cyan         ?? "\033[36m";
$reset        = $reset        ?? "\033[0m";
$verdeOliva   = $verdeOliva   ?? "\033[38;5;64m";
$doradoOscuro = $doradoOscuro ?? "\033[38;5;178m";

if (!function_exists('dashboardResumen')) {
    /** Mismo cálculo que AdminDashboardRepository::obtenerResumen() pero con PDO. */
    function dashboardResumen(PDO $conexion): array
    {
        $ahora = new DateTimeImmutable('now', new DateTimeZone('America/Bogota'));
        $hoy = $ahora->setTime(0, 0);
        $inicioDia = $hoy->format('Y-m-d H:i:s');
        $inicioManana = $hoy->modify('+1 day')->format('Y-m-d H:i:s');
        $instante = $ahora->format('Y-m-d H:i:s');

        $stmt = $conexion->prepare(
            "SELECT
                (SELECT COUNT(*) FROM habitacion) AS total_habitaciones,
                (SELECT COUNT(DISTINCT d.cod_hab_det)
                 FROM detalle d
                 INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                 WHERE d.cod_hab_det IS NOT NULL
                   AND " . \App\Admin\AdminDashboardRepository::condicionEstanciaEnCurso('r') . ") AS habitaciones_ocupadas,
                (SELECT COUNT(*) FROM reservas
                 WHERE fec_ent_res >= ?
                   AND fec_ent_res < ?
                   AND est_res NOT IN ('Cancelada', 'Cancelado', 'Finalizada')) AS check_ins_hoy,
                (SELECT COALESCE(SUM(monto), 0) FROM pagos
                 WHERE estado_pago = 'Aprobado'
                   AND fecha_pago >= ?
                   AND fecha_pago < ?) AS ingresos_hoy,
                (SELECT COUNT(*) FROM tarea WHERE est_tar = 'Pendiente') AS tareas_pendientes"
        );
        $stmt->execute([$instante, $instante, $inicioDia, $inicioManana, $inicioDia, $inicioManana]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        $total = (int) $r['total_habitaciones'];
        $ocupadas = (int) $r['habitaciones_ocupadas'];

        return [
            'habitaciones_ocupadas' => $ocupadas,
            'total_habitaciones'    => $total,
            'ocupacion'             => $total > 0 ? (int) round(($ocupadas / $total) * 100) : 0,
            'check_ins_hoy'         => (int) $r['check_ins_hoy'],
            'ingresos_hoy'          => (float) $r['ingresos_hoy'],
            'tareas_pendientes'     => (int) $r['tareas_pendientes'],
        ];
    }
}

if (!function_exists('dashboardEventosRecientes')) {
    /** Mismo cálculo que AdminDashboardRepository::obtenerEventosRecientes(). */
    function dashboardEventosRecientes(int $limite = 5): array
    {
        $directorio = __DIR__ . '/../../logs';
        if (!is_dir($directorio)) {
            return [];
        }
        $archivos = glob($directorio . '/app-*.log') ?: [];
        rsort($archivos, SORT_STRING);
        $archivos = array_slice($archivos, 0, 4);

        $eventos = [];
        foreach ($archivos as $archivo) {
            $size = (int) @filesize($archivo);
            $fh = @fopen($archivo, 'rb');
            if ($fh === false) {
                continue;
            }
            $longitud = min($size, 1048576);
            if ($longitud > 0) {
                fseek($fh, -$longitud, SEEK_END);
            }
            $contenido = $longitud > 0 ? (string) fread($fh, $longitud) : '';
            fclose($fh);

            foreach (array_reverse(preg_split('/\R/', $contenido) ?: []) as $linea) {
                if ($linea === '') {
                    continue;
                }
                $reg = json_decode($linea, true);
                if (!is_array($reg)
                    || !is_string($reg['timestamp'] ?? null)
                    || !is_string($reg['level'] ?? null)
                    || !is_string($reg['message'] ?? null)) {
                    continue;
                }
                $eventos[] = [
                    'timestamp' => $reg['timestamp'],
                    'level'     => strtoupper($reg['level']),
                    'message'   => $reg['message'],
                ];
                if (count($eventos) >= $limite) {
                    break 2;
                }
            }
        }
        return $eventos;
    }
}

limpiarPantalla();
loading();
echo "\r\033[K";

try {
    $resumen = dashboardResumen($conexion);
    $eventos = dashboardEventosRecientes();
} catch (Throwable $e) {
    echo "{$rojo}✖ No se pudo cargar el dashboard: " . $e->getMessage() . "{$reset}\n";
    $resumen = null;
    $eventos = [];
}

if ($resumen !== null) {
    echo "{$verde}✔ ¡Dashboard cargado correctamente!{$reset}\n\n";
    echo "{$doradoOscuro}══════════════ DASHBOARD · HOTEL AURORA ══════════════{$reset}\n";
    echo "{$azul}Actualizado: " . (new DateTimeImmutable('now', new DateTimeZone('America/Bogota')))->format('d/m/Y H:i:s') . "{$reset}\n\n";

    // Barra de ocupación
    $largoBarra = 30;
    $llenas = (int) round(($resumen['ocupacion'] / 100) * $largoBarra);
    $barra = str_repeat('█', $llenas) . str_repeat('░', $largoBarra - $llenas);

    echo "{$amarillo}OCUPACIÓN{$reset}\n";
    echo "  {$verdeOliva}{$barra}{$reset} {$resumen['ocupacion']}%\n";
    echo "  {$resumen['habitaciones_ocupadas']} de {$resumen['total_habitaciones']} habitaciones ocupadas\n\n";

    echo "{$amarillo}LLEGADAS HOY{$reset}\n";
    echo "  {$resumen['check_ins_hoy']}\n\n";

    echo "{$amarillo}PAGOS DE RESERVAS HOY{$reset}\n";
    echo "  $" . number_format($resumen['ingresos_hoy'], 0, ',', '.') . "\n\n";

    echo "{$amarillo}TAREAS PENDIENTES{$reset}\n";
    echo "  {$resumen['tareas_pendientes']}\n\n";

    echo "{$doradoOscuro}── Actividad reciente del sistema ──{$reset}\n";
    if ($eventos === []) {
        echo "  Todavía no hay eventos recientes registrados.\n";
    } else {
        foreach ($eventos as $ev) {
            $colorNivel = match ($ev['level']) {
                'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' => $rojo,
                'WARNING', 'WARN'                          => $amarillo,
                default                                    => $cyan,
            };
            echo "  {$colorNivel}[{$ev['level']}]{$reset} {$ev['timestamp']}\n";
            echo "      " . $ev['message'] . "\n";
        }
    }
    echo "\n";
}

$validOptions = ["1", "2", "3", "0"];
do {
    echo "1. Actualizar dashboard" . PHP_EOL;
    echo "2. Ver tareas" . PHP_EOL;
    echo "3. Volver al menú principal" . PHP_EOL;
    echo "0. Cerrar sesion" . PHP_EOL;

    $dashSelector = (string) readline("Seleccione una opción para continuar: ");
    limpiarPantalla();
    if (!in_array($dashSelector, $validOptions, true)) {
        echo "opcion no valida" . PHP_EOL;
        echo "ingrese una opcion valida" . PHP_EOL;
    }

    switch ($dashSelector) {
        case "1":
            require __DIR__ . "/viewDashboard.php";
            break 2;
        case "2":
            require __DIR__ . "/viewTasks.php";
            break 2;
        case "3":
            require __DIR__ . "/selectTasks.php";
            break 2;
        case "0":
            echo "Cerrando sesion tareas." . PHP_EOL;
            require_once __DIR__ . '/../security/logout.php';
            exit(0);
    }
} while ($dashSelector !== "0");