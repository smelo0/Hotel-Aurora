<?php
declare(strict_types=1);

namespace App\Reembolso;

use App\Billing\BillingRegistrar;
use DateTimeImmutable;
use DateTimeZone;
use mysqli;
use RuntimeException;

/**
 * Solicitudes de reembolso de reservas pagadas.
 *
 * Flujo: Solicitado (el huésped cancela y pide el dinero) -> Aprobado (el hotel acepta)
 *        -> Pagado (el hotel ya devolvió el dinero y registra la referencia).
 *        Alternativa: Solicitado -> Rechazado (la reserva se restaura si sus habitaciones siguen libres).
 *
 * La devolución en sí se hace fuera del sistema (transferencia, reverso en Wompi, efectivo); aquí queda
 * el control y el rastro contable (tabla `reembolsos` + factura negativa tipo 'reembolso').
 */
final class ReembolsoRepository
{
    public const ESTADOS = ['Solicitado', 'Aprobado', 'Rechazado', 'Pagado'];

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
            $db->query(
                "CREATE TABLE IF NOT EXISTS `reembolsos` (
                    `id_reembolso` bigint(20) NOT NULL AUTO_INCREMENT,
                    `cod_res_reemb` bigint(20) NOT NULL,
                    `id_usu_reemb` bigint(20) NOT NULL,
                    `monto_pagado` decimal(12,2) NOT NULL,
                    `porcentaje` tinyint(3) unsigned NOT NULL,
                    `monto_reembolso` decimal(12,2) NOT NULL,
                    `motivo` varchar(500) DEFAULT NULL,
                    `estado` enum('Solicitado','Aprobado','Rechazado','Pagado') NOT NULL DEFAULT 'Solicitado',
                    `estado_previo_res` varchar(20) DEFAULT NULL,
                    `nota_admin` varchar(500) DEFAULT NULL,
                    `referencia_devolucion` varchar(100) DEFAULT NULL,
                    `id_admin_reemb` bigint(20) DEFAULT NULL,
                    `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
                    `resuelto_en` datetime DEFAULT NULL,
                    PRIMARY KEY (`id_reembolso`),
                    KEY `idx_reemb_reserva` (`cod_res_reemb`),
                    KEY `idx_reemb_usuario` (`id_usu_reemb`),
                    KEY `idx_reemb_estado` (`estado`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
            );
            $db->query(
                "INSERT IGNORE INTO `permiso` (`cod_permiso`, `modulo`, `accion`, `des_permiso`) VALUES
                    ('reembolsos.ver', 'reembolsos', 'ver', 'Ver las solicitudes de reembolso'),
                    ('reembolsos.gestionar', 'reembolsos', 'gestionar', 'Aprobar, rechazar y registrar reembolsos')"
            );
            // El gerente (rol 2) los recibe por defecto; el admin (rol 1) ya tiene todo.
            $db->query(
                "INSERT IGNORE INTO `rol_permiso` (`cod_rol`, `cod_permiso`)
                 SELECT 2, p.cod_permiso FROM `permiso` p
                 WHERE p.cod_permiso IN ('reembolsos.ver', 'reembolsos.gestionar')
                   AND EXISTS (SELECT 1 FROM `rol` WHERE cod_rol = 2)"
            );
            BillingRegistrar::asegurarEsquema($db);
            // Reembolsos ya pagados que aún no tienen su movimiento negativo en `pagos`.
            $db->query(
                "INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago, concepto, fecha_pago)
                 SELECT rb.cod_res_reemb, -rb.monto_reembolso, 'Devolución', CONCAT('REE-', rb.id_reembolso), 'Aprobado', 'reembolso', COALESCE(rb.resuelto_en, NOW())
                 FROM reembolsos rb
                 WHERE rb.estado = 'Pagado'
                   AND NOT EXISTS (SELECT 1 FROM pagos p WHERE p.referencia_pago = CONCAT('REE-', rb.id_reembolso))"
            );
            $db->query("UPDATE factura SET est_fac = 'Reembolsada' WHERE tipo_fac = 'reembolso' AND est_fac <> 'Reembolsada'");
        } catch (\Throwable $error) {
            error_log('No se pudo preparar el esquema de reembolsos: ' . $error->getMessage());
        }
    }

    private static function ahora(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('America/Bogota'));
    }

    /**
     * Calcula si la reserva del huésped puede reembolsarse y cuánto.
     * Devuelve ['elegible' => bool, 'motivo' => string, 'pagado', 'porcentaje', 'monto'].
     */
    public function cotizar(int $codRes, int $idUsuario, bool $bloquear = false): array
    {
        $sql = "SELECT cod_res, fec_res, fec_ent_res, est_res FROM reservas WHERE cod_res = ? AND id_usu_res = ? LIMIT 1"
            . ($bloquear ? ' FOR UPDATE' : '');
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $codRes, $idUsuario);
        $stmt->execute();
        $reserva = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $vacio = ['elegible' => false, 'motivo' => '', 'pagado' => 0.0, 'porcentaje' => 0, 'monto' => 0.0, 'estado_res' => ''];
        if (!$reserva) {
            return ['motivo' => 'La reserva no existe o no te pertenece.'] + $vacio;
        }
        $vacio['estado_res'] = (string) $reserva['est_res'];
        $estado = mb_strtolower(trim((string) $reserva['est_res']));
        if (!in_array($estado, ['pendiente', 'confirmada'], true)) {
            return ['motivo' => self::mensajeEstadoNoReembolsable($estado)] + $vacio;
        }
        if (new DateTimeImmutable((string) $reserva['fec_ent_res'], new DateTimeZone('America/Bogota')) <= self::ahora()) {
            return ['motivo' => 'La fecha de check-in de esta reserva ya pasó, por lo que ya no admite reembolso automático. Comunícate con el hotel.'] + $vacio;
        }

        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(monto), 0) FROM pagos
             WHERE cod_res_pago = ? AND estado_pago = 'Aprobado' AND concepto = 'alojamiento'"
        );
        $stmt->bind_param('i', $codRes);
        $stmt->execute();
        $stmt->bind_result($pagado);
        $stmt->fetch();
        $stmt->close();
        $pagado = round((float) $pagado, 2);
        $vacio['pagado'] = $pagado;
        if ($pagado <= 0) {
            return ['motivo' => 'Esta reserva no tiene pagos de alojamiento aprobados.'] + $vacio;
        }

        $stmt = $this->db->prepare("SELECT 1 FROM reembolsos WHERE cod_res_reemb = ? AND estado <> 'Rechazado' LIMIT 1");
        $stmt->bind_param('i', $codRes);
        $stmt->execute();
        $stmt->store_result();
        $yaExiste = $stmt->num_rows > 0;
        $stmt->close();
        if ($yaExiste) {
            return ['motivo' => 'Ya existe una solicitud de reembolso para esta reserva.'] + $vacio;
        }

        $zona = new DateTimeZone('America/Bogota');
        $porcentaje = PoliticaReembolso::porcentaje(
            self::ahora(),
            new DateTimeImmutable((string) $reserva['fec_res'], $zona),
            new DateTimeImmutable((string) $reserva['fec_ent_res'], $zona)
        );
        $vacio['porcentaje'] = $porcentaje;
        if ($porcentaje <= 0) {
            return ['motivo' => 'Quedan menos días de los permitidos para un reembolso. ' . PoliticaReembolso::descripcion() . '.'] + $vacio;
        }

        return [
            'elegible' => true,
            'motivo' => '',
            'pagado' => $pagado,
            'porcentaje' => $porcentaje,
            'monto' => round($pagado * $porcentaje / 100, 2),
            'estado_res' => (string) $reserva['est_res'],
        ];
    }

    /** Cancela la reserva y deja la solicitud en estado Solicitado. Devuelve el id del reembolso. */
    public function solicitar(int $codRes, int $idUsuario, string $motivo): int
    {
        $this->db->begin_transaction();
        try {
            $cotizacion = $this->cotizar($codRes, $idUsuario, true);
            if (!$cotizacion['elegible']) {
                throw new RuntimeException($cotizacion['motivo']);
            }
            $motivo = mb_substr(trim($motivo), 0, 500);
            $estadoPrevio = $cotizacion['estado_res'];

            $stmt = $this->db->prepare(
                "INSERT INTO reembolsos (cod_res_reemb, id_usu_reemb, monto_pagado, porcentaje, monto_reembolso, motivo, estado_previo_res)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iididss', $codRes, $idUsuario, $cotizacion['pagado'], $cotizacion['porcentaje'], $cotizacion['monto'], $motivo, $estadoPrevio);
            $stmt->execute();
            $id = (int) $this->db->insert_id;
            $stmt->close();
            if ($id < 1) {
                throw new RuntimeException('No se pudo registrar la solicitud.');
            }

            $stmt = $this->db->prepare("UPDATE reservas SET est_res = 'Cancelada' WHERE cod_res = ? AND id_usu_res = ?");
            $stmt->bind_param('ii', $codRes, $idUsuario);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return $id;
        } catch (\Throwable $error) {
            $this->db->rollback();
            throw $error instanceof RuntimeException ? $error : new RuntimeException('No se pudo procesar la solicitud.', 0, $error);
        }
    }

    /** Estado del reembolso más reciente por reserva, para pintar el portal del huésped. */
    public function porReservasDelUsuario(int $idUsuario): array
    {
        $stmt = $this->db->prepare(
            'SELECT cod_res_reemb, estado, monto_reembolso, porcentaje FROM reembolsos
             WHERE id_usu_reemb = ? ORDER BY id_reembolso ASC'
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $mapa = [];
        foreach ($filas as $fila) {
            $mapa[(int) $fila['cod_res_reemb']] = $fila; // el último sobrescribe
        }
        return $mapa;
    }

    public function listar(?string $estado = null): array
    {
        $sql = "SELECT rb.*, u.nom_usu, u.corr_usu, r.fec_ent_res, r.fec_sal_res, r.est_res AS est_res_actual
                FROM reembolsos rb
                LEFT JOIN usuario u ON u.id_usu = rb.id_usu_reemb
                LEFT JOIN reservas r ON r.cod_res = rb.cod_res_reemb";
        if ($estado !== null && in_array($estado, self::ESTADOS, true)) {
            $stmt = $this->db->prepare($sql . ' WHERE rb.estado = ? ORDER BY rb.id_reembolso DESC LIMIT 300');
            $stmt->bind_param('s', $estado);
        } else {
            $stmt = $this->db->prepare($sql . ' ORDER BY FIELD(rb.estado, \'Solicitado\', \'Aprobado\', \'Pagado\', \'Rechazado\'), rb.id_reembolso DESC LIMIT 300');
        }
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $filas;
    }

    public function resumen(): array
    {
        $resultado = $this->db->query(
            "SELECT estado, COUNT(*) AS total, COALESCE(SUM(monto_reembolso), 0) AS monto FROM reembolsos GROUP BY estado"
        );
        $resumen = [];
        foreach (self::ESTADOS as $estado) {
            $resumen[$estado] = ['total' => 0, 'monto' => 0.0];
        }
        if ($resultado instanceof \mysqli_result) {
            while ($fila = $resultado->fetch_assoc()) {
                $resumen[$fila['estado']] = ['total' => (int) $fila['total'], 'monto' => (float) $fila['monto']];
            }
        }
        return $resumen;
    }

    /**
     * Cambia el estado de una solicitud.
     * $accion: aprobar | rechazar | pagar. Lanza RuntimeException con un mensaje listo para mostrar.
     */
    public function resolver(int $idReembolso, string $accion, int $idAdmin, string $nota = '', string $referencia = ''): string
    {
        $nota = mb_substr(trim($nota), 0, 500);
        $referencia = mb_substr(trim($referencia), 0, 100);

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('SELECT * FROM reembolsos WHERE id_reembolso = ? FOR UPDATE');
            $stmt->bind_param('i', $idReembolso);
            $stmt->execute();
            $rb = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$rb) {
                throw new RuntimeException('La solicitud no existe.');
            }
            $codRes = (int) $rb['cod_res_reemb'];

            if ($accion === 'aprobar') {
                if ($rb['estado'] !== 'Solicitado') {
                    throw new RuntimeException('Solo se pueden aprobar solicitudes en estado Solicitado.');
                }
                $this->actualizar($idReembolso, 'Aprobado', $idAdmin, $nota, null);
                $mensaje = 'Reembolso aprobado. Cuando devuelvas el dinero, regístralo como pagado.';
            } elseif ($accion === 'rechazar') {
                if ($rb['estado'] !== 'Solicitado') {
                    throw new RuntimeException('Solo se pueden rechazar solicitudes en estado Solicitado.');
                }
                if ($nota === '') {
                    throw new RuntimeException('Escribe el motivo del rechazo para que el huésped lo vea.');
                }
                $this->restaurarReserva($codRes, (string) ($rb['estado_previo_res'] ?: 'Confirmada'));
                $this->actualizar($idReembolso, 'Rechazado', $idAdmin, $nota, null);
                $mensaje = 'Solicitud rechazada y reserva restaurada.';
            } elseif ($accion === 'pagar') {
                if ($rb['estado'] !== 'Aprobado') {
                    throw new RuntimeException('Primero debes aprobar la solicitud.');
                }
                if ($referencia === '') {
                    throw new RuntimeException('Escribe la referencia asociada a la reserva (n.º de factura o referencia del pago).');
                }
                $referencia = $this->validarReferencia($codRes, $referencia);
                $this->actualizar($idReembolso, 'Pagado', $idAdmin, $nota, $referencia);
                // Movimiento negativo en `pagos`: así Finanzas, el dashboard y los totales descuentan la devolución.
                $montoNegativo = -1 * (float) $rb['monto_reembolso'];
                $referenciaPago = 'REE-' . $idReembolso;
                $stmt = $this->db->prepare(
                    "INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago, concepto)
                     VALUES (?, ?, 'Devolución', ?, 'Aprobado', 'reembolso')"
                );
                $stmt->bind_param('ids', $codRes, $montoNegativo, $referenciaPago);
                $stmt->execute();
                $stmt->close();
                BillingRegistrar::registrarFactura(
                    $this->db,
                    'REE-' . $idReembolso,
                    'reembolso',
                    $codRes,
                    -1 * (float) $rb['monto_reembolso'],
                    'Devolución',
                    [
                        'reembolso' => $idReembolso,
                        'porcentaje' => (int) $rb['porcentaje'],
                        'monto_pagado_original' => (float) $rb['monto_pagado'],
                        'referencia_devolucion' => $referencia,
                    ]
                );
                $stmt = $this->db->prepare("UPDATE factura SET est_fac = 'Reembolsada' WHERE folio = ?");
                $stmt->bind_param('s', $referenciaPago);
                $stmt->execute();
                $stmt->close();
                $mensaje = 'Reembolso registrado como pagado.';
            } else {
                throw new RuntimeException('Acción no válida.');
            }

            $this->db->commit();
            return $mensaje;
        } catch (\Throwable $error) {
            $this->db->rollback();
            throw $error instanceof RuntimeException ? $error : new RuntimeException('No se pudo actualizar la solicitud.', 0, $error);
        }
    }

    private static function mensajeEstadoNoReembolsable(string $estado): string
    {
        return match ($estado) {
            'en casa' => 'Tu estancia está en curso, por lo que ya no se puede reembolsar desde aquí. Habla con recepción.',
            'cancelada', 'cancelado' => 'Esta reserva ya fue cancelada.',
            'finalizada' => 'Esta reserva ya finalizó, por lo que no admite reembolso.',
            default => 'Esta reserva no se puede reembolsar en su estado actual.',
        };
    }

    /** Reservas pagadas que el personal todavía puede reembolsar (para el selector del admin). */
    public function reservasReembolsables(): array
    {
        $resultado = $this->db->query(
            "SELECT r.cod_res, u.nom_usu, r.est_res, r.fec_ent_res, SUM(pg.monto) AS pagado
             FROM reservas r
             INNER JOIN usuario u ON u.id_usu = r.id_usu_res
             INNER JOIN pagos pg ON pg.cod_res_pago = r.cod_res AND pg.estado_pago = 'Aprobado' AND pg.concepto = 'alojamiento'
             WHERE LOWER(TRIM(COALESCE(r.est_res, ''))) IN ('pendiente', 'confirmada', 'en casa')
               AND NOT EXISTS (SELECT 1 FROM reembolsos rb WHERE rb.cod_res_reemb = r.cod_res AND rb.estado <> 'Rechazado')
             GROUP BY r.cod_res, u.nom_usu, r.est_res, r.fec_ent_res
             ORDER BY r.cod_res DESC LIMIT 200"
        );
        return $resultado instanceof \mysqli_result ? $resultado->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * El personal reembolsa directamente una reserva pagada: queda Aprobado y la reserva se cancela.
     * Si trae $referencia, la devolución ya se hizo y pasa a Pagado. Devuelve un mensaje para mostrar.
     */
    public function crearComoAdmin(int $codRes, int $idAdmin, int $porcentaje, string $motivo, string $referencia = ''): string
    {
        if ($porcentaje < 1 || $porcentaje > 100) {
            throw new RuntimeException('El porcentaje debe estar entre 1 y 100.');
        }
        $motivo = mb_substr(trim($motivo), 0, 500);
        $referencia = trim($referencia);
        if ($referencia !== '') {
            $referencia = $this->validarReferencia($codRes, $referencia);
        }

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('SELECT id_usu_res, est_res FROM reservas WHERE cod_res = ? FOR UPDATE');
            $stmt->bind_param('i', $codRes);
            $stmt->execute();
            $reserva = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$reserva) {
                throw new RuntimeException('La reserva no existe.');
            }
            $estado = mb_strtolower(trim((string) $reserva['est_res']));
            if (!in_array($estado, ['pendiente', 'confirmada', 'en casa'], true)) {
                throw new RuntimeException(self::mensajeEstadoNoReembolsable($estado));
            }

            $stmt = $this->db->prepare(
                "SELECT COALESCE(SUM(monto), 0) FROM pagos
                 WHERE cod_res_pago = ? AND estado_pago = 'Aprobado' AND concepto = 'alojamiento'"
            );
            $stmt->bind_param('i', $codRes);
            $stmt->execute();
            $stmt->bind_result($pagado);
            $stmt->fetch();
            $stmt->close();
            $pagado = round((float) $pagado, 2);
            if ($pagado <= 0) {
                throw new RuntimeException('Esta reserva no tiene pagos de alojamiento aprobados.');
            }

            $stmt = $this->db->prepare("SELECT 1 FROM reembolsos WHERE cod_res_reemb = ? AND estado <> 'Rechazado' LIMIT 1");
            $stmt->bind_param('i', $codRes);
            $stmt->execute();
            $stmt->store_result();
            $yaExiste = $stmt->num_rows > 0;
            $stmt->close();
            if ($yaExiste) {
                throw new RuntimeException('Ya existe un reembolso para esta reserva.');
            }

            $monto = round($pagado * $porcentaje / 100, 2);
            $idUsuario = (int) $reserva['id_usu_res'];
            $estadoPrevio = (string) $reserva['est_res'];
            $stmt = $this->db->prepare(
                "INSERT INTO reembolsos (cod_res_reemb, id_usu_reemb, monto_pagado, porcentaje, monto_reembolso, motivo, estado, estado_previo_res, id_admin_reemb, resuelto_en)
                 VALUES (?, ?, ?, ?, ?, ?, 'Aprobado', ?, ?, NOW())"
            );
            $stmt->bind_param('iididssi', $codRes, $idUsuario, $pagado, $porcentaje, $monto, $motivo, $estadoPrevio, $idAdmin);
            $stmt->execute();
            $id = (int) $this->db->insert_id;
            $stmt->close();
            if ($id < 1) {
                throw new RuntimeException('No se pudo registrar el reembolso.');
            }

            $stmt = $this->db->prepare("UPDATE reservas SET est_res = 'Cancelada' WHERE cod_res = ?");
            $stmt->bind_param('i', $codRes);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollback();
            throw $error instanceof RuntimeException ? $error : new RuntimeException('No se pudo registrar el reembolso.', 0, $error);
        }

        if ($referencia !== '') {
            return $this->resolver($id, 'pagar', $idAdmin, '', $referencia);
        }
        return 'Reembolso registrado y aprobado. Cuando devuelvas el dinero, márcalo como pagado.';
    }

    /**
     * Referencias que pertenecen a cada reserva: la referencia de sus pagos de alojamiento aprobados
     * y el folio de su factura de alojamiento. Devuelve [cod_res => [referencias]].
     */
    public function referenciasPorReservas(array $codigos): array
    {
        $codigos = array_values(array_unique(array_filter(array_map('intval', $codigos))));
        if ($codigos === []) {
            return [];
        }
        $in = implode(',', $codigos); // solo enteros: seguro para interpolar
        $mapa = [];
        $consultas = [
            "SELECT cod_res_pago AS cod, referencia_pago AS ref FROM pagos
             WHERE cod_res_pago IN ($in) AND concepto = 'alojamiento' AND estado_pago = 'Aprobado'
               AND referencia_pago IS NOT NULL AND referencia_pago <> ''",
            "SELECT cod_res_fac AS cod, COALESCE(folio, CONCAT('FAC-', cod_fac)) AS ref FROM factura
             WHERE cod_res_fac IN ($in) AND COALESCE(tipo_fac, 'alojamiento') = 'alojamiento'",
        ];
        foreach ($consultas as $sql) {
            $resultado = $this->db->query($sql);
            if ($resultado instanceof \mysqli_result) {
                while ($fila = $resultado->fetch_assoc()) {
                    $cod = (int) $fila['cod'];
                    $ref = trim((string) $fila['ref']);
                    if ($ref !== '' && !in_array($ref, $mapa[$cod] ?? [], true)) {
                        $mapa[$cod][] = $ref;
                    }
                }
            }
        }
        return $mapa;
    }

    /** Devuelve la referencia tal como está registrada o lanza un error si no pertenece a la reserva. */
    private function validarReferencia(int $codRes, string $referencia): string
    {
        $validas = $this->referenciasPorReservas([$codRes])[$codRes] ?? [];
        if ($validas === []) {
            throw new RuntimeException('Esta reserva no tiene referencias de pago ni factura registradas. Genera su factura antes de registrar la devolución.');
        }
        foreach ($validas as $valida) {
            if (mb_strtoupper($valida) === mb_strtoupper(trim($referencia))) {
                return $valida;
            }
        }
        throw new RuntimeException('La referencia no corresponde a la reserva #' . $codRes . '. Referencias válidas: ' . implode(', ', $validas) . '.');
    }

    private function actualizar(int $id, string $estado, int $idAdmin, string $nota, ?string $referencia): void
    {
        $stmt = $this->db->prepare(
            "UPDATE reembolsos SET estado = ?, id_admin_reemb = ?, resuelto_en = NOW(),
                    nota_admin = COALESCE(NULLIF(?, ''), nota_admin),
                    referencia_devolucion = COALESCE(?, referencia_devolucion)
             WHERE id_reembolso = ?"
        );
        $stmt->bind_param('sissi', $estado, $idAdmin, $nota, $referencia, $id);
        $stmt->execute();
        $stmt->close();
    }

    /** Devuelve la reserva a su estado previo, solo si sus habitaciones no se reservaron mientras tanto. */
    private function restaurarReserva(int $codRes, string $estadoPrevio): void
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM reservas r0
             INNER JOIN detalle d0 ON d0.cod_res_det = r0.cod_res
             INNER JOIN detalle d1 ON d1.cod_hab_det = d0.cod_hab_det AND d1.cod_res_det <> r0.cod_res
             INNER JOIN reservas r1 ON r1.cod_res = d1.cod_res_det
             WHERE r0.cod_res = ?
               AND LOWER(TRIM(COALESCE(r1.est_res, ''))) NOT IN ('cancelada', 'cancelado', 'finalizada')
               AND r1.fec_ent_res < r0.fec_sal_res AND r1.fec_sal_res > r0.fec_ent_res
             LIMIT 1"
        );
        $stmt->bind_param('i', $codRes);
        $stmt->execute();
        $stmt->store_result();
        $hayConflicto = $stmt->num_rows > 0;
        $stmt->close();
        if ($hayConflicto) {
            throw new RuntimeException('No se puede rechazar: la habitación ya fue reservada por otro huésped en esas fechas. Aprueba el reembolso o contacta al huésped.');
        }
        $stmt = $this->db->prepare("UPDATE reservas SET est_res = ? WHERE cod_res = ? AND est_res = 'Cancelada'");
        $stmt->bind_param('si', $estadoPrevio, $codRes);
        $stmt->execute();
        $stmt->close();
    }
}