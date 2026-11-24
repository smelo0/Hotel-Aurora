<?php
declare(strict_types=1);

namespace App\Billing;

use mysqli;
use RuntimeException;

/**
 * Historial de facturas para el personal.
 *
 *  - facturas.ver              -> ve las últimas LIMITE_RECEPCION facturas (recepción).
 *  - facturas.ver_todas        -> ve y busca todo el historial (admin / gerente).
 *  - facturas.aprobar_acceso   -> aprueba o rechaza solicitudes de acceso completo.
 *
 * Quien solo tiene facturas.ver puede pedir acceso completo: el admin lo aprueba y se concede por
 * MINUTOS_ACCESO minutos (tabla factura_acceso).
 */
final class FacturaHistorial
{
    public const LIMITE_RECEPCION = 5;
    public const MINUTOS_ACCESO = 60;

    private static bool $verificado = false;

    public function __construct(private mysqli $db)
    {
        self::asegurarEsquema($db);
    }

    public static function asegurarEsquema(mysqli $db): void
    {
        if (self::$verificado) {
            return;
        }
        self::$verificado = true;
        try {
            BillingRegistrar::asegurarEsquema($db);
            $db->query(
                "CREATE TABLE IF NOT EXISTS `factura_acceso` (
                    `id_acceso` bigint(20) NOT NULL AUTO_INCREMENT,
                    `id_empleado` bigint(20) NOT NULL,
                    `estado` enum('Pendiente','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
                    `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
                    `resuelto_en` datetime DEFAULT NULL,
                    `id_admin` bigint(20) DEFAULT NULL,
                    `vence_en` datetime DEFAULT NULL,
                    PRIMARY KEY (`id_acceso`),
                    KEY `idx_fa_empleado` (`id_empleado`, `estado`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
            $db->query(
                "INSERT IGNORE INTO `permiso` (`cod_permiso`, `modulo`, `accion`, `des_permiso`) VALUES
                    ('facturas.ver', 'facturas', 'ver', 'Ver las últimas facturas'),
                    ('facturas.ver_todas', 'facturas', 'ver_todas', 'Ver y buscar todo el historial de facturas'),
                    ('facturas.aprobar_acceso', 'facturas', 'aprobar_acceso', 'Aprobar solicitudes de acceso al historial completo')"
            );
            // Permisos por defecto solo la primera vez, para no pisar lo que el gerente cambie después.
            if ($db->affected_rows > 0) {
                $db->query(
                    "INSERT IGNORE INTO `rol_permiso` (`cod_rol`, `cod_permiso`)
                     SELECT 7, 'facturas.ver' FROM DUAL WHERE EXISTS (SELECT 1 FROM `rol` WHERE cod_rol = 7)"
                );
                $db->query(
                    "INSERT IGNORE INTO `rol_permiso` (`cod_rol`, `cod_permiso`)
                     SELECT 2, p.cod_permiso FROM `permiso` p
                     WHERE p.cod_permiso LIKE 'facturas.%' AND EXISTS (SELECT 1 FROM `rol` WHERE cod_rol = 2)"
                );
            }
        } catch (\Throwable $error) {
            error_log('No se pudo preparar el historial de facturas: ' . $error->getMessage());
        }
    }

    private function filas(string $sql, string $tipos = '', array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        if ($params !== []) {
            $stmt->bind_param($tipos, ...$params);
        }
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $filas;
    }

    // ---------- Facturas ----------

    public function listar(bool $todas, string $busqueda = ''): array
    {
        $sql = "SELECT f.cod_fac, f.fech_fac, f.tot_fac, f.est_fac, f.cod_res_fac,
                       COALESCE(f.folio, CONCAT('FAC-', f.cod_fac)) AS folio,
                       f.tipo_fac, f.metodo_pago_fac, f.detalle_fac, u.nom_usu, u.corr_usu
                FROM factura f
                LEFT JOIN reservas r ON r.cod_res = f.cod_res_fac
                LEFT JOIN usuario u ON u.id_usu = r.id_usu_res";
        $tipos = '';
        $params = [];
        $busqueda = trim(mb_substr($busqueda, 0, 100));
        if ($todas && $busqueda !== '') {
            $sql .= ' WHERE (f.folio LIKE ? OR u.nom_usu LIKE ? OR u.corr_usu LIKE ? OR f.cod_res_fac = ?)';
            $like = '%' . $busqueda . '%';
            $numero = ctype_digit($busqueda) ? (int) $busqueda : 0;
            $tipos = 'sssi';
            $params = [$like, $like, $like, $numero];
        }
        $sql .= ' ORDER BY f.fech_fac DESC, f.cod_fac DESC LIMIT ' . ($todas ? 300 : self::LIMITE_RECEPCION);

        $resultado = [];
        foreach ($this->filas($sql, $tipos, $params) as $fila) {
            $detalle = json_decode((string) ($fila['detalle_fac'] ?? ''), true);
            $detalle = is_array($detalle) ? $detalle : [];
            $resultado[] = [
                'cod_fac' => (int) $fila['cod_fac'],
                'folio' => (string) $fila['folio'],
                'fecha' => (string) $fila['fech_fac'],
                'total' => (float) $fila['tot_fac'],
                'estado' => (string) ($fila['est_fac'] ?? ''),
                'tipo' => (string) ($fila['tipo_fac'] ?? 'alojamiento'),
                'metodo' => (string) ($fila['metodo_pago_fac'] ?? ''),
                'cod_reserva' => $fila['cod_res_fac'] !== null ? (int) $fila['cod_res_fac'] : null,
                'huesped' => (string) ($fila['nom_usu'] ?? $detalle['huesped'] ?? ''),
                'correo' => (string) ($fila['corr_usu'] ?? $detalle['correo'] ?? ''),
            ];
        }
        return $resultado;
    }

    private function ultimosIds(): array
    {
        $filas = $this->filas('SELECT cod_fac FROM factura ORDER BY fech_fac DESC, cod_fac DESC LIMIT ' . self::LIMITE_RECEPCION);
        return array_map(static fn(array $f): int => (int) $f['cod_fac'], $filas);
    }

    /** Devuelve la factura con la forma que espera imprimirFacturaReserva() de admin.js. */
    public function obtener(int $id, bool $todas): array
    {
        if (!$todas && !in_array($id, $this->ultimosIds(), true)) {
            throw new RuntimeException('Esta factura es anterior a las últimas ' . self::LIMITE_RECEPCION . '. Solicita acceso completo al administrador.', 403);
        }

        $filas = $this->filas(
            "SELECT f.cod_fac, f.fech_fac, f.tot_fac, f.cod_res_fac, f.folio, f.tipo_fac, f.metodo_pago_fac, f.detalle_fac,
                    u.nom_usu, u.corr_usu, r.fec_ent_res, r.fec_sal_res,
                    GROUP_CONCAT(DISTINCT h.num_hab ORDER BY h.num_hab ASC SEPARATOR ', ') AS habitaciones,
                    GROUP_CONCAT(DISTINCT h.tipo_hab ORDER BY h.tipo_hab ASC SEPARATOR ', ') AS tipos
             FROM factura f
             LEFT JOIN reservas r ON r.cod_res = f.cod_res_fac
             LEFT JOIN usuario u ON u.id_usu = r.id_usu_res
             LEFT JOIN detalle d ON d.cod_res_det = r.cod_res
             LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
             WHERE f.cod_fac = ?
             GROUP BY f.cod_fac, f.fech_fac, f.tot_fac, f.cod_res_fac, f.folio, f.tipo_fac, f.metodo_pago_fac, f.detalle_fac,
                      u.nom_usu, u.corr_usu, r.fec_ent_res, r.fec_sal_res",
            'i',
            [$id]
        );
        $f = $filas[0] ?? null;
        if (!$f) {
            throw new RuntimeException('La factura no existe.', 404);
        }

        $detalle = json_decode((string) ($f['detalle_fac'] ?? ''), true);
        $detalle = is_array($detalle) ? $detalle : [];
        $folio = (string) ($f['folio'] ?: 'FAC-' . $f['cod_fac']);
        $huesped = (string) ($f['nom_usu'] ?? $detalle['huesped'] ?? '');
        $correo = (string) ($f['corr_usu'] ?? $detalle['correo'] ?? '');

        if (($f['tipo_fac'] ?? '') === 'reembolso') {
            $referencia = !empty($detalle['referencia_devolucion']) ? ' · ref. ' . $detalle['referencia_devolucion'] : '';
            return [
                'tipo' => 'adicional',
                'es_reembolso' => true,
                'folio' => $folio,
                'cod_reserva' => $f['cod_res_fac'],
                'fecha' => (string) $f['fech_fac'],
                'huesped' => $huesped,
                'correo' => $correo,
                'descripcion' => 'Reembolso de alojamiento (' . (int) ($detalle['porcentaje'] ?? 0) . ' %)' . $referencia,
                'metodo_pago' => 'Devolución',
                'monto' => abs((float) $f['tot_fac']),
            ];
        }

        if (isset($detalle['tipo'], $detalle['folio'])) {
            $detalle['fecha'] = $detalle['fecha'] ?? (string) $f['fech_fac'];
            $detalle['huesped'] = $detalle['huesped'] ?? $huesped;
            $detalle['correo'] = $detalle['correo'] ?? $correo;
            return $detalle;
        }

        // Facturas antiguas sin detalle guardado: se reconstruyen con los datos de la reserva.
        $entrada = (string) ($f['fec_ent_res'] ?? '');
        $salida = (string) ($f['fec_sal_res'] ?? '');
        $noches = ($entrada !== '' && $salida !== '')
            ? max(1, (int) round((strtotime($salida) - strtotime($entrada)) / 86400))
            : 1;
        return [
            'tipo' => 'alojamiento',
            'folio' => $folio,
            'cod_reserva' => $f['cod_res_fac'],
            'fecha' => (string) $f['fech_fac'],
            'huesped' => $huesped,
            'correo' => $correo,
            'habitacion' => (string) ($f['habitaciones'] ?: 'Sin asignar'),
            'tipo_habitacion' => (string) ($f['tipos'] ?? ''),
            'fecha_entrada' => $entrada,
            'fecha_salida' => $salida,
            'noches' => $noches,
            'metodo_pago' => (string) ($f['metodo_pago_fac'] ?? ''),
            'monto' => (float) $f['tot_fac'],
        ];
    }

    // ---------- Facturas del propio huésped ----------

    /** Facturas de una reserva, solo si la reserva pertenece al huésped. */
    public function facturasDeReserva(int $codRes, int $idUsuario): array
    {
        $filas = $this->filas(
            "SELECT f.cod_fac, COALESCE(f.folio, CONCAT('FAC-', f.cod_fac)) AS folio, f.tipo_fac, f.tot_fac, f.fech_fac, f.metodo_pago_fac
             FROM factura f
             INNER JOIN reservas r ON r.cod_res = f.cod_res_fac
             WHERE f.cod_res_fac = ? AND r.id_usu_res = ?
             ORDER BY f.fech_fac ASC, f.cod_fac ASC",
            'ii',
            [$codRes, $idUsuario]
        );
        return array_map(static fn(array $f): array => [
            'cod_fac' => (int) $f['cod_fac'],
            'folio' => (string) $f['folio'],
            'tipo' => (string) ($f['tipo_fac'] ?? 'alojamiento'),
            'total' => (float) $f['tot_fac'],
            'fecha' => (string) $f['fech_fac'],
            'metodo' => (string) ($f['metodo_pago_fac'] ?? ''),
        ], $filas);
    }

    /** Una factura lista para ver/imprimir, solo si pertenece a una reserva del huésped. */
    public function obtenerDeUsuario(int $id, int $idUsuario): array
    {
        $dueno = $this->filas(
            'SELECT 1 FROM factura f INNER JOIN reservas r ON r.cod_res = f.cod_res_fac WHERE f.cod_fac = ? AND r.id_usu_res = ? LIMIT 1',
            'ii',
            [$id, $idUsuario]
        );
        if ($dueno === []) {
            throw new RuntimeException('La factura no existe.', 404);
        }
        return $this->obtener($id, true);
    }

    /** De las reservas dadas, cuáles tienen al menos una factura (para mostrar el botón en el historial). */
    public function reservasConFacturas(array $codigos, int $idUsuario): array
    {
        $codigos = array_values(array_unique(array_filter(array_map('intval', $codigos))));
        if ($codigos === []) {
            return [];
        }
        $in = implode(',', $codigos); // solo enteros: seguro para interpolar
        $filas = $this->filas(
            "SELECT DISTINCT f.cod_res_fac FROM factura f
             INNER JOIN reservas r ON r.cod_res = f.cod_res_fac
             WHERE r.id_usu_res = ? AND f.cod_res_fac IN ($in)",
            'i',
            [$idUsuario]
        );
        return array_map(static fn(array $f): int => (int) $f['cod_res_fac'], $filas);
    }

    // ---------- Acceso completo (solicitud y aprobación) ----------

    public function accesoTemporalVigente(int $idEmpleado): ?array
    {
        $filas = $this->filas(
            "SELECT id_acceso, vence_en, GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), vence_en)) AS segundos
             FROM factura_acceso WHERE id_empleado = ? AND estado = 'Aprobada' AND vence_en > NOW()
             ORDER BY vence_en DESC LIMIT 1",
            'i',
            [$idEmpleado]
        );
        return $filas[0] ?? null;
    }

    public function ultimaSolicitud(int $idEmpleado): ?array
    {
        $filas = $this->filas(
            'SELECT id_acceso, estado, creado_en, vence_en FROM factura_acceso WHERE id_empleado = ? ORDER BY id_acceso DESC LIMIT 1',
            'i',
            [$idEmpleado]
        );
        return $filas[0] ?? null;
    }

    public function solicitarAcceso(int $idEmpleado): void
    {
        $ultima = $this->ultimaSolicitud($idEmpleado);
        if ($ultima && $ultima['estado'] === 'Pendiente') {
            throw new RuntimeException('Ya tienes una solicitud pendiente. Espera la respuesta del administrador.');
        }
        $stmt = $this->db->prepare("INSERT INTO factura_acceso (id_empleado, estado) VALUES (?, 'Pendiente')");
        $stmt->bind_param('i', $idEmpleado);
        $stmt->execute();
        $stmt->close();
    }

    public function solicitudesPendientes(): array
    {
        return $this->filas(
            "SELECT fa.id_acceso, fa.creado_en, u.nom_usu
             FROM factura_acceso fa LEFT JOIN usuario u ON u.id_usu = fa.id_empleado
             WHERE fa.estado = 'Pendiente' ORDER BY fa.id_acceso ASC"
        );
    }

    public function resolverAcceso(int $idAcceso, bool $aprobar, int $idAdmin): void
    {
        $estado = $aprobar ? 'Aprobada' : 'Rechazada';
        $stmt = $this->db->prepare(
            "UPDATE factura_acceso
             SET estado = ?, id_admin = ?, resuelto_en = NOW(),
                 vence_en = IF(?, DATE_ADD(NOW(), INTERVAL " . self::MINUTOS_ACCESO . " MINUTE), NULL)
             WHERE id_acceso = ? AND estado = 'Pendiente'"
        );
        $flag = $aprobar ? 1 : 0;
        $stmt->bind_param('siii', $estado, $idAdmin, $flag, $idAcceso);
        $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();
        if ($afectadas < 1) {
            throw new RuntimeException('La solicitud ya fue resuelta o no existe.');
        }
    }
}