<?php
declare(strict_types=1);

namespace App\Admin;

use mysqli;

final class AdminReservationRepository
{
    private const PAGE_SIZE = 25;

    public function __construct(private mysqli $connection)
    {
    }

    public function obtenerDatos(int $requestedPage = 1, string $search = ''): array
    {
        $search = trim(mb_substr($search, 0, 100, 'UTF-8'));
        $searchSql = $search === '' ? '' : ' WHERE u.nom_usu LIKE ?';
        $countStatement = $this->connection->prepare(
            'SELECT COUNT(*) AS total FROM reservas r INNER JOIN usuario u ON r.id_usu_res = u.id_usu'
            . $searchSql
        );
        if (!$countStatement) {
            return $this->obtenerEstructuraVacia($requestedPage, $search);
        }
        if ($search !== '') {
            $searchPattern = '%' . $search . '%';
            $countStatement->bind_param('s', $searchPattern);
        }
        if (!$countStatement->execute()) {
            $countStatement->close();
            return $this->obtenerEstructuraVacia($requestedPage, $search);
        }
        $total = (int) ($countStatement->get_result()->fetch_assoc()['total'] ?? 0);
        $countStatement->close();
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min(max(1, $requestedPage), $totalPages);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $searchPattern = '%' . $search . '%';

        $reservas = $this->obtenerFilas(
            "SELECT r.cod_res, u.nom_usu, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res,
                    GROUP_CONCAT(
                        DISTINCT CONCAT(h.num_hab, ' · ', h.tipo_hab)
                        ORDER BY h.num_hab ASC SEPARATOR ', '
                    ) AS habitaciones,
                    COALESCE(
                        (SELECT SUM(pg.monto) FROM pagos pg WHERE pg.cod_res_pago = r.cod_res AND pg.estado_pago = 'Pendiente'),
                        GREATEST(
                            COALESCE((
                                SELECT SUM(COALESCE(NULLIF(h2.pre_hab, 0), h2.precio_hab, 0))
                                FROM detalle d2
                                INNER JOIN habitacion h2 ON h2.cod_hab = d2.cod_hab_det
                                WHERE d2.cod_res_det = r.cod_res
                            ), 0) * DATEDIFF(r.fec_sal_res, r.fec_ent_res) * 1.19
                            - COALESCE((SELECT SUM(pg2.monto) FROM pagos pg2 WHERE pg2.cod_res_pago = r.cod_res AND pg2.estado_pago = 'Aprobado'), 0),
                            0
                        )
                    ) AS saldo_pendiente
             FROM reservas r
             INNER JOIN usuario u ON r.id_usu_res = u.id_usu
             LEFT JOIN detalle d ON r.cod_res = d.cod_res_det
             LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
             " . ($search !== '' ? 'WHERE u.nom_usu LIKE ?' : '') . "
             GROUP BY r.cod_res, u.nom_usu, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res
             ORDER BY r.cod_res DESC LIMIT ? OFFSET ?",
            $search !== '' ? 'sii' : 'ii',
            $search !== '' ? [$searchPattern, self::PAGE_SIZE, $offset] : [self::PAGE_SIZE, $offset]
        );

        $experienciasPorReserva = [];
        $codigosReservas = array_filter(array_map(static fn($reserva): int => is_array($reserva) ? (int) ($reserva['cod_res'] ?? 0) : 0, $reservas));
        
        $experiencias = $codigosReservas === [] ? [] : $this->obtenerFilas(
            "SELECT id_agenda, cod_res_agenda, actividad, fecha_agenda, hora_agenda,
                    estado_agenda, estado_pago_experiencia, monto_experiencia
             FROM agenda_actividad
             WHERE cod_res_agenda IN (" . implode(',', array_fill(0, count($codigosReservas), '?')) . ")
             ORDER BY fecha_agenda, hora_agenda, id_agenda",
            str_repeat('i', count($codigosReservas)),
            array_values($codigosReservas)
        );
        foreach ($experiencias as $experiencia) {
            if (is_array($experiencia) && isset($experiencia['cod_res_agenda'])) {
                $experienciasPorReserva[(int) $experiencia['cod_res_agenda']][] = $experiencia;
            }
        }

        $huespedes = $this->obtenerFilas(
            'SELECT id_usu, nom_usu, corr_usu FROM usuario
             WHERE cod_rol_usu = 6 AND est_usu = 1 ORDER BY nom_usu ASC'
        );

        $habitaciones = $this->obtenerFilas(
            "SELECT cod_hab, num_hab, tipo_hab, pre_hab, precio_hab
             FROM habitacion WHERE est_hab = 'Disponible' ORDER BY num_hab ASC"
        );

        return [
            'reservas' => $reservas,
            'experiencias_por_reserva' => $experienciasPorReserva,
            'huespedes' => $huespedes,
            'habitaciones_disponibles' => $habitaciones,
            'paginacion' => [
                'pagina' => $page,
                'paginas' => $totalPages,
                'por_pagina' => self::PAGE_SIZE,
                'total' => $total,
                'desde' => $total === 0 ? 0 : $offset + 1,
                'hasta' => min($offset + self::PAGE_SIZE, $total),
                'busqueda' => $search,
            ],
        ];
    }

    private function obtenerFilas(string $sql, string $types = '', array $params = []): array
    {
        try {
            if ($types === '') {
                $resultado = $this->connection->query($sql);
            } else {
                $statement = $this->connection->prepare($sql);
                if (!$statement) {
                    return [];
                }
                $references = [];
                foreach ($params as $index => &$value) {
                    $references[$index] = &$value;
                }
                unset($value);
                if (!call_user_func_array([$statement, 'bind_param'], array_merge([$types], $references)) || !$statement->execute()) {
                    $statement->close();
                    return [];
                }
                $resultado = $statement->get_result();
            }

            if (!$resultado instanceof \mysqli_result) {
                return [];
            }

            $filas = [];
            while ($fila = $resultado->fetch_assoc()) {
                $filas[] = $fila;
            }
            if (isset($statement) && $statement) {
                $statement->close();
            }
            return $filas;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function obtenerEstructuraVacia(int $page, string $search): array
    {
        return [
            'reservas' => [],
            'experiencias_por_reserva' => [],
            'huespedes' => [],
            'habitaciones_disponibles' => [],
            'paginacion' => [
                'pagina' => $page,
                'paginas' => 1,
                'por_pagina' => self::PAGE_SIZE,
                'total' => 0,
                'desde' => 0,
                'hasta' => 0,
                'busqueda' => $search,
            ],
        ];
    }
}