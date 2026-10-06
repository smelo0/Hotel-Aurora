<?php
declare(strict_types=1);

namespace App\Admin;

use mysqli;

final class AdminFinanceService
{
    public function __construct(private mysqli $connection)
    {
    }

    public function obtenerDatos(): array
    {
        $pagos = [];
        $totales = [];
        $resultadoPagos = $this->obtenerFilas(
            "SELECT p.id_pago, p.cod_res_pago, p.monto, p.metodo_pago, p.estado_pago, p.fecha_pago,
                    r.id_usu_res, r.fec_ent_res, r.fec_sal_res,
                    u.nom_usu, u.corr_usu, COALESCE(habitaciones.descripcion, 'Sin habitación registrada') AS habitaciones
             FROM pagos p
             INNER JOIN reservas r ON r.cod_res = p.cod_res_pago
             LEFT JOIN usuario u ON u.id_usu = r.id_usu_res
             LEFT JOIN (
                 SELECT d.cod_res_det,
                        GROUP_CONCAT(DISTINCT CONCAT('Habitación ', h.num_hab, ' · ', h.tipo_hab) SEPARATOR ', ') AS descripcion
                 FROM detalle d
                 INNER JOIN habitacion h ON h.cod_hab = d.cod_hab_det
                 GROUP BY d.cod_res_det
             ) habitaciones ON habitaciones.cod_res_det = r.cod_res
             ORDER BY p.fecha_pago DESC, p.id_pago DESC"
        );
        foreach ($resultadoPagos as $pago) {
            $pagos[] = $pago;
            $idUsuario = (int) ($pago['id_usu_res'] ?? 0);
            $identificador = $idUsuario > 0
                ? 'usuario:' . $idUsuario
                : 'sin-cuenta:' . mb_strtolower(trim((string) ($pago['corr_usu'] ?? $pago['nom_usu'] ?? 'desconocido')), 'UTF-8');
            $this->asegurarTotalCliente(
                $totales,
                $identificador,
                (string) ($pago['nom_usu'] ?? 'Huésped sin cuenta asociada'),
                (string) ($pago['corr_usu'] ?? '')
            );
            if ($pago['estado_pago'] === 'Aprobado') {
                $totales[$identificador]['total_pagado'] += (float) $pago['monto'];
                $totales[$identificador]['pagos_aprobados']++;
            }
        }

        $experiencias = (new \App\Experiencia\ExperienceRepository())
            ->obtenerHistorial($this->connection);
        $movimientosExperiencias = [];
        foreach ($experiencias as $experiencia) {
            $idUsuario = (int) ($experiencia['id_usu_agenda'] ?? 0);
            $identificador = $idUsuario > 0
                ? 'usuario:' . $idUsuario
                : 'sin-cuenta:' . mb_strtolower(trim((string) ($experiencia['correo_cliente'] ?? $experiencia['nombre_cliente'] ?? 'desconocido')), 'UTF-8');
            $this->asegurarTotalCliente(
                $totales,
                $identificador,
                (string) ($experiencia['nombre_cliente'] ?? 'Huésped sin cuenta asociada'),
                (string) ($experiencia['correo_cliente'] ?? '')
            );
            $experiencia['estado_pago_experiencia'] = (string) ($experiencia['estado_pago_experiencia'] ?? 'Pendiente');
            if ($experiencia['estado_pago_experiencia'] === 'Pagada' && $experiencia['monto_experiencia'] !== null) {
                $totales[$identificador]['total_pagado'] += (float) $experiencia['monto_experiencia'];
                $totales[$identificador]['pagos_aprobados']++;
            }
            $movimientosExperiencias[] = $experiencia;
        }

        uasort($totales, static fn(array $a, array $b): int => $b['total_pagado'] <=> $a['total_pagado']);

        return [
            'pagos' => $pagos,
            'totales' => $totales,
            'movimientos_experiencias' => $movimientosExperiencias,
        ];
    }

    private function asegurarTotalCliente(array &$totales, string $identificador, string $nombre, string $correo): void
    {
        if (!isset($totales[$identificador])) {
            $totales[$identificador] = [
                'nombre' => $nombre,
                'correo' => $correo,
                'total_pagado' => 0.0,
                'pagos_aprobados' => 0,
            ];
        }
    }

    private function obtenerFilas(string $sql): array
    {
        $resultado = $this->connection->query($sql);
        if (!$resultado instanceof \mysqli_result) {
            throw new \RuntimeException('No se pudieron cargar los datos financieros del administrador.');
        }

        $filas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
        return $filas;
    }
}
