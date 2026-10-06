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
            throw new \RuntimeException('No se pudo contar las reservas del administrador.');
        }
        if ($search !== '') {
            $searchPattern = '%' . $search . '%';
            $countStatement->bind_param('s', $searchPattern);
        }
        if (!$countStatement->execute()) {
            $countStatement->close();
            throw new \RuntimeException('No se pudo contar las reservas del administrador.');
        }
        $total = (int) ($countStatement->get_result()->fetch_assoc()['total'] ?? 0);
        $countStatement->close();
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min(max(1, $requestedPage), $totalPages);
        $offset = ($page - 1) * self::PAGE_SIZE;
        $searchPattern = '%' . $search . '%';

        $reservas = $this->obtenerFilas(
            "SELECT r.cod_res, u.nom_usu, r.fec_ent_res, r.fec_sal_res, r.est_res, r.not_res, d.cod_hab_det,
                    COALESCE(
                        (SELECT SUM(pg.monto) FROM pagos pg WHERE pg.cod_res_pago = r.cod_res AND pg.estado_pago = 'Pendiente'),
                        GREATEST(
                            COALESCE(NULLIF(h.pre_hab, 0), h.precio_hab, 0) * DATEDIFF(r.fec_sal_res, r.fec_ent_res) * 1.19
                            - COALESCE((SELECT SUM(pg2.monto) FROM pagos pg2 WHERE pg2.cod_res_pago = r.cod_res AND pg2.estado_pago = 'Aprobado'), 0),
                            0
                        )
                    ) AS saldo_pendiente
             FROM reservas r
             INNER JOIN usuario u ON r.id_usu_res = u.id_usu
             LEFT JOIN detalle d ON r.cod_res = d.cod_res_det
             LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
             " . ($search !== '' ? 'WHERE u.nom_usu LIKE ?' : '') . "
             ORDER BY r.cod_res DESC LIMIT ? OFFSET ?",
            $search !== '' ? 'sii' : 'ii',
            $search !== '' ? [$searchPattern, self::PAGE_SIZE, $offset] : [self::PAGE_SIZE, $offset]
        );

        $experienciasPorReserva = [];
        $codigosReservas = array_map(static fn(array $reserva): int => (int) $reserva['cod_res'], $reservas);
        $experiencias = $codigosReservas === [] ? [] : $this->obtenerFilas(
            "SELECT id_agenda, cod_res_agenda, actividad, fecha_agenda, hora_agenda,
                    estado_agenda, estado_pago_experiencia, monto_experiencia
             FROM agenda_actividad
             WHERE cod_res_agenda IN (" . implode(',', array_fill(0, count($codigosReservas), '?')) . ")
             ORDER BY fecha_agenda, hora_agenda, id_agenda",
            str_repeat('i', count($codigosReservas)),
            $codigosReservas
        );
        foreach ($experiencias as $experiencia) {
            $experienciasPorReserva[(int) $experiencia['cod_res_agenda']][] = $experiencia;
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
        if ($types === '') {
            $resultado = $this->connection->query($sql);
        } else {
            $statement = $this->connection->prepare($sql);
            if (!$statement) {
                throw new \RuntimeException('No se pudieron cargar los datos de reservas del administrador.');
            }
            $references = [];
            foreach ($params as $index => &$value) {
                $references[$index] = &$value;
            }
            unset($value);
            if (!call_user_func_array([$statement, 'bind_param'], array_merge([$types], $references)) || !$statement->execute()) {
                $statement->close();
                throw new \RuntimeException('No se pudieron cargar los datos de reservas del administrador.');
            }
            $resultado = $statement->get_result();
        }
        if (!$resultado instanceof \mysqli_result) {
            throw new \RuntimeException('No se pudieron cargar los datos de reservas del administrador.');
        }

        $filas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
        if (isset($statement)) {
            $statement->close();
        }
        return $filas;
    }
}
