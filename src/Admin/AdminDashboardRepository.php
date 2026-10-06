<?php
declare(strict_types=1);

namespace App\Admin;

use mysqli;

final class AdminDashboardRepository
{
    public function __construct(private mysqli $connection)
    {
    }

    public function obtenerResumen(): array
    {
        $zonaHorariaHotel = new \DateTimeZone('America/Bogota');
        $ahora = new \DateTimeImmutable('now', $zonaHorariaHotel);
        $hoy = $ahora->setTime(0, 0);
        $manana = $hoy->modify('+1 day');
        $inicioDia = $hoy->format('Y-m-d H:i:s');
        $inicioManana = $manana->format('Y-m-d H:i:s');

        $stmt = $this->connection->prepare(
            "SELECT
                (SELECT COUNT(*) FROM habitacion) AS total_habitaciones,
                (SELECT COUNT(DISTINCT d.cod_hab_det)
                 FROM detalle d
                 INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                 WHERE d.cod_hab_det IS NOT NULL
                   AND r.est_res IN ('Confirmada', 'En Casa')
                   AND ? >= r.fec_ent_res
                   AND ? < r.fec_sal_res) AS habitaciones_ocupadas,
                (SELECT COUNT(*) FROM reservas
                 WHERE fec_ent_res >= ?
                   AND fec_ent_res < ?
                   AND est_res NOT IN ('Cancelada', 'Cancelado', 'Finalizada')) AS check_ins_hoy,
                (SELECT COALESCE(SUM(monto), 0) FROM pagos
                 WHERE estado_pago = 'Aprobado'
                   AND fecha_pago >= ?
                   AND fecha_pago < ?) AS ingresos_hoy"
        );
        if (!$stmt) {
            throw new \RuntimeException('No se pudo preparar el resumen operativo del administrador.');
        }
        $instanteActual = $ahora->format('Y-m-d H:i:s');
        if (
            !$stmt->bind_param('ssssss', $instanteActual, $instanteActual, $inicioDia, $inicioManana, $inicioDia, $inicioManana)
            || !$stmt->execute()
        ) {
            $stmt->close();
            throw new \RuntimeException('No se pudo consultar el resumen operativo del administrador.');
        }
        $resultado = $stmt->get_result();

        $resumen = $resultado->fetch_assoc();
        $stmt->close();
        if (!is_array($resumen)) {
            throw new \RuntimeException('No se pudo cargar el resumen operativo del administrador.');
        }

        $totalHabitaciones = (int) $resumen['total_habitaciones'];
        $habitacionesOcupadas = (int) $resumen['habitaciones_ocupadas'];

        return [
            'ocupacion' => $totalHabitaciones > 0
                ? (int) round(($habitacionesOcupadas / $totalHabitaciones) * 100)
                : 0,
            'check_ins_hoy' => (int) $resumen['check_ins_hoy'],
            'ingresos_hoy' => (float) $resumen['ingresos_hoy'],
        ];
    }

    public function obtenerEventosRecientes(int $limite = 5): array
    {
        $limite = max(1, min($limite, 20));
        $directorioLogs = __DIR__ . '/../../logs';
        if (!is_dir($directorioLogs)) {
            return [];
        }

        $archivos = glob($directorioLogs . '/app-*.log');
        if ($archivos === false) {
            throw new \RuntimeException('No se pudieron localizar los registros recientes del sistema.');
        }
        rsort($archivos, SORT_STRING);
        $archivos = array_slice($archivos, 0, 4);

        $eventos = [];
        foreach ($archivos as $archivo) {
            $manejador = fopen($archivo, 'rb');
            if ($manejador === false) {
                throw new \RuntimeException('No se pudo leer el registro reciente del sistema.');
            }

            $estadisticas = fstat($manejador);
            if ($estadisticas === false) {
                fclose($manejador);
                throw new \RuntimeException('No se pudo consultar el tamaño del registro del sistema.');
            }
            $longitud = min((int) $estadisticas['size'], 1048576);
            if ($longitud > 0 && fseek($manejador, -$longitud, SEEK_END) !== 0) {
                fclose($manejador);
                throw new \RuntimeException('No se pudo acceder al final del registro del sistema.');
            }
            $contenido = $longitud > 0 ? fread($manejador, $longitud) : '';
            fclose($manejador);
            if ($contenido === false) {
                throw new \RuntimeException('No se pudo leer el registro del sistema.');
            }

            $lineas = preg_split('/\R/', $contenido);
            if (!is_array($lineas)) {
                continue;
            }
            foreach (array_reverse($lineas) as $linea) {
                if ($linea === '') {
                    continue;
                }
                $registro = json_decode($linea, true);
                if (
                    !is_array($registro)
                    || !is_string($registro['timestamp'] ?? null)
                    || !is_string($registro['level'] ?? null)
                    || !is_string($registro['message'] ?? null)
                ) {
                    continue;
                }

                $eventos[] = [
                    'timestamp' => $registro['timestamp'],
                    'level' => strtoupper($registro['level']),
                    'message' => $registro['message'],
                ];
                if (count($eventos) >= $limite) {
                    break 2;
                }
            }
        }

        return $eventos;
    }
}
