<?php
require_once __DIR__ . '/../includes/sesion_seguridad.php';
require_once '../configuracion/conexion.php';
require_once '../configuracion/permiso.php';

// Incluimos Composer y la clase Logger
require_once __DIR__ . '/../vendor/autoload.php';
use App\Logger;

header('Content-Type: application/json; charset=utf-8');
/**@var mysqli $conexion */
exigir_permiso($conexion, 'reservas.editar');

// Obtenemos el ID del usuario actual de la sesión para auditoría
$idUsuarioLog = $_SESSION['emp_auth']['id_usuario'] ?? $_SESSION['user_auth']['id_usuario'] ?? 0;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigir_csrf();
    $transaccionIniciada = false;
    try {
        $cod_res = filter_var($_POST['cod_res'] ?? null, FILTER_VALIDATE_INT);
        $habitacion = trim((string) ($_POST['habitacion'] ?? ''));
        $estadoSolicitado = trim((string) ($_POST['estado'] ?? ''));
        $notas = trim((string) ($_POST['notas'] ?? ''));
        $accion = trim((string) ($_POST['accion'] ?? 'guardar'));
        $metodoPago = trim((string) ($_POST['metodo_pago'] ?? ''));

        if (!$cod_res || !in_array($accion, ['guardar', 'cobrar'], true)) {
            throw new RuntimeException('datos_invalidos');
        }

        if ($accion === 'cobrar' && !in_array($metodoPago, ['Efectivo', 'Tarjeta', 'Transferencia'], true)) {
            throw new RuntimeException('metodo_pago_invalido');
        }

        if ($accion === 'guardar' && !in_array($estadoSolicitado, ['Pendiente', 'Confirmada', 'En Casa', 'Cancelada', 'Finalizada'], true)) {
            throw new RuntimeException('estado_invalido');
        }

        $conexion->begin_transaction();
        $transaccionIniciada = true;

        $sqlReserva = "SELECT r.est_res, r.fec_ent_res, r.fec_sal_res, u.nom_usu, u.corr_usu,
                              d.cod_hab_det, h.num_hab, h.tipo_hab,
                              COALESCE(NULLIF(h.pre_hab, 0), h.precio_hab, 0) AS precio_habitacion
                       FROM reservas r
                       INNER JOIN usuario u ON u.id_usu = r.id_usu_res
                       LEFT JOIN detalle d ON d.cod_res_det = r.cod_res
                       LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
                       WHERE r.cod_res = ?
                       LIMIT 1
                       FOR UPDATE";
        $stmtReserva = $conexion->prepare($sqlReserva);
        $stmtReserva->bind_param('i', $cod_res);
        $stmtReserva->execute();
        $reservaResult = $stmtReserva->get_result();
        $reservaActual = $reservaResult->fetch_assoc();
        $stmtReserva->close();

        if (!$reservaActual) {
            throw new RuntimeException('reserva_no_encontrada');
        }

        if ($accion === 'cobrar' && $reservaActual['est_res'] !== 'Pendiente') {
            throw new RuntimeException('reserva_no_pendiente');
        }

        // Las habitaciones de las nuevas reservas se asignan automáticamente por tipo/cantidad.
        // No se permite que la edición convierta una reserva múltiple en una sola habitación.
        $hab_final = null;

        $estadoFinal = $accion === 'cobrar' ? 'Confirmada' : $estadoSolicitado;
        if (
            $accion === 'guardar'
            && $estadoFinal === 'Confirmada'
            && $reservaActual['est_res'] !== 'Confirmada'
        ) {
            $stmtPagoAprobado = $conexion->prepare(
                "SELECT 1 FROM pagos WHERE cod_res_pago = ? AND estado_pago = 'Aprobado' LIMIT 1"
            );
            $stmtPagoAprobado->bind_param('i', $cod_res);
            $stmtPagoAprobado->execute();
            $stmtPagoAprobado->store_result();
            $tienePagoAprobado = $stmtPagoAprobado->num_rows > 0;
            $stmtPagoAprobado->close();
            if (!$tienePagoAprobado) {
                throw new RuntimeException('pago_no_confirmado');
            }
        }

        $factura = null;
        if ($accion === 'cobrar') {
            $pagosPendientes = $conexion->prepare(
                "SELECT id_pago, monto FROM pagos WHERE cod_res_pago = ? AND estado_pago = 'Pendiente' FOR UPDATE"
            );
            $pagosPendientes->bind_param('i', $cod_res);
            $pagosPendientes->execute();
            $pagosResult = $pagosPendientes->get_result();
            $idsPagosPendientes = [];
            $montoCobrado = 0.0;
            while ($pagoPendiente = $pagosResult->fetch_assoc()) {
                $idsPagosPendientes[] = (int) $pagoPendiente['id_pago'];
                $montoCobrado += (float) $pagoPendiente['monto'];
            }
            $pagosPendientes->close();

            if ($montoCobrado <= 0) {
                $stmtPagosAprobados = $conexion->prepare(
                    "SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE cod_res_pago = ? AND estado_pago = 'Aprobado'"
                );
                $stmtPagosAprobados->bind_param('i', $cod_res);
                $stmtPagosAprobados->execute();
                $stmtPagosAprobados->bind_result($montoYaPagado);
                $stmtPagosAprobados->fetch();
                $stmtPagosAprobados->close();

                $entrada = new DateTimeImmutable((string) $reservaActual['fec_ent_res']);
                $salida = new DateTimeImmutable((string) $reservaActual['fec_sal_res']);
                $noches = (int) $entrada->diff($salida)->days;
                $stmtTotalHabitaciones = $conexion->prepare(
                    'SELECT COALESCE(SUM(COALESCE(NULLIF(h.pre_hab, 0), h.precio_hab, 0)), 0)
                     FROM detalle d INNER JOIN habitacion h ON h.cod_hab = d.cod_hab_det
                     WHERE d.cod_res_det = ?'
                );
                $stmtTotalHabitaciones->bind_param('i', $cod_res);
                $stmtTotalHabitaciones->execute();
                $stmtTotalHabitaciones->bind_result($precioNocheTotal);
                $stmtTotalHabitaciones->fetch();
                $stmtTotalHabitaciones->close();
                $totalReserva = round((float) $precioNocheTotal * $noches * 1.19, 2);
                $montoCobrado = round(max(0, $totalReserva - (float) $montoYaPagado), 2);
            }

            if ($montoCobrado <= 0) {
                throw new RuntimeException('sin_saldo_pendiente');
            }

            if ($idsPagosPendientes !== []) {
                $stmtActualizarPagos = $conexion->prepare(
                    "UPDATE pagos SET metodo_pago = ?, estado_pago = 'Aprobado', fecha_pago = CURRENT_TIMESTAMP
                     WHERE cod_res_pago = ? AND estado_pago = 'Pendiente'"
                );
                $stmtActualizarPagos->bind_param('si', $metodoPago, $cod_res);
                $stmtActualizarPagos->execute();
                $idPagoFactura = end($idsPagosPendientes);
                $stmtActualizarPagos->close();
            } else {
                $referenciaPago = 'REF-' . bin2hex(random_bytes(16));
                $estadoPago = 'Aprobado';
                $stmtInsertarPago = $conexion->prepare(
                    'INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago) VALUES (?, ?, ?, ?, ?)'
                );
                $stmtInsertarPago->bind_param('idsss', $cod_res, $montoCobrado, $metodoPago, $referenciaPago, $estadoPago);
                $stmtInsertarPago->execute();
                $idPagoFactura = (int) $conexion->insert_id;
                $stmtInsertarPago->close();
            }
        }

        $sqlActualizarReserva = 'UPDATE reservas SET est_res = ?, not_res = ? WHERE cod_res = ?';
        $stmtActualizarReserva = $conexion->prepare($sqlActualizarReserva);
        $stmtActualizarReserva->bind_param('ssi', $estadoFinal, $notas, $cod_res);
        $stmtActualizarReserva->execute();
        $stmtActualizarReserva->close();

        $sqlActualizada = "SELECT r.cod_res, u.nom_usu, u.corr_usu, r.fec_ent_res, r.fec_sal_res,
                                  r.est_res, r.not_res, d.cod_hab_det, h.num_hab, h.tipo_hab
                           FROM reservas r
                           INNER JOIN usuario u ON u.id_usu = r.id_usu_res
                           LEFT JOIN detalle d ON d.cod_res_det = r.cod_res
                           LEFT JOIN habitacion h ON h.cod_hab = d.cod_hab_det
                           WHERE r.cod_res = ?
                           LIMIT 1";
        $stmtActualizada = $conexion->prepare($sqlActualizada);
        $stmtActualizada->bind_param('i', $cod_res);
        $stmtActualizada->execute();
        $reservaResult = $stmtActualizada->get_result();
        $reserva_actualizada = $reservaResult->fetch_assoc();
        $stmtActualizada->close();

        if (!$reserva_actualizada) {
            throw new RuntimeException('reserva_no_encontrada');
        }

        if ($accion === 'cobrar') {
            $factura = [
                'folio' => 'FAC-' . $idPagoFactura,
                'cod_reserva' => (int) $reserva_actualizada['cod_res'],
                'fecha' => date('Y-m-d H:i:s'),
                'huesped' => (string) $reserva_actualizada['nom_usu'],
                'correo' => (string) $reserva_actualizada['corr_usu'],
                'habitacion' => (string) ($reserva_actualizada['num_hab'] ?? 'Sin asignar'),
                'tipo_habitacion' => (string) ($reserva_actualizada['tipo_hab'] ?? ''),
                'fecha_entrada' => (string) $reserva_actualizada['fec_ent_res'],
                'fecha_salida' => (string) $reserva_actualizada['fec_sal_res'],
                'noches' => (int) (new DateTimeImmutable((string) $reserva_actualizada['fec_ent_res']))
                    ->diff(new DateTimeImmutable((string) $reserva_actualizada['fec_sal_res']))->days,
                'metodo_pago' => $metodoPago,
                'monto' => $montoCobrado,
            ];
        }

        $conexion->commit();

        Logger::registrarLog('INFO', $accion === 'cobrar' ? 'Pago de reserva registrado y confirmado' : 'Reserva actualizada con éxito', [
            'id_usuario' => $idUsuarioLog,
            'cod_res' => $cod_res,
            'nuevo_estado' => $estadoFinal,
            'habitacion_asignada' => $hab_final,
            'metodo_pago' => $accion === 'cobrar' ? $metodoPago : null,
            'monto_cobrado' => $accion === 'cobrar' ? $montoCobrado : null,
        ]);

        echo json_encode([
            'status' => 'exito',
            'mensaje' => $accion === 'cobrar' ? 'Pago registrado y reserva confirmada.' : 'Reserva actualizada correctamente.',
            'reserva' => [
                'cod_res' => (int) $reserva_actualizada['cod_res'],
                'nom_usu' => $reserva_actualizada['nom_usu'],
                'fec_ent_res' => $reserva_actualizada['fec_ent_res'],
                'fec_sal_res' => $reserva_actualizada['fec_sal_res'],
                'est_res' => $reserva_actualizada['est_res'],
                'not_res' => $reserva_actualizada['not_res'] ?? '',
                'cod_hab_det' => $reserva_actualizada['cod_hab_det'],
                'habitacion' => [
                    'num_hab' => $reserva_actualizada['num_hab'],
                    'tipo_hab' => $reserva_actualizada['tipo_hab'],
                ],
                'pago' => $accion === 'cobrar' ? [
                    'monto' => $montoCobrado,
                    'metodo' => $metodoPago,
                    'estado' => 'Aprobado',
                ] : null,
                'factura' => $factura,
            ],
        ]);
        exit();
    } catch (Throwable $e) {
        if ($transaccionIniciada) {
            try {
                $conexion->rollback();
            } catch (Throwable $rollbackError) {
            }
        }

        Logger::registrarLog('ERROR', 'Fallo al actualizar la reserva en base de datos', [
            'id_usuario' => $idUsuarioLog,
            'cod_res' => $_POST['cod_res'] ?? null,
            'error_excepcion' => $e->getMessage(),
            'error_db' => $conexion->error
        ]);

        $mensajes = [
            'datos_invalidos' => 'Los datos enviados para la reserva no son válidos.',
            'reserva_no_encontrada' => 'No se encontró la reserva seleccionada.',
            'reserva_no_pendiente' => 'Solo se puede cobrar una reserva pendiente.',
            'metodo_pago_invalido' => 'Selecciona un método de pago válido.',
            'estado_invalido' => 'Selecciona un estado válido.',
            'habitacion_invalida' => 'La habitación indicada no es válida.',
            'habitacion_no_disponible' => 'La habitación no está disponible para las fechas de esta reserva.',
            'habitacion_no_existe' => 'La habitación indicada no es válida.',
            'habitacion_reservada' => 'La habitación ya está reservada para estas fechas.',
            'pago_no_confirmado' => 'No se puede confirmar la reserva sin un pago aprobado.',
            'sin_saldo_pendiente' => 'La reserva no tiene un saldo pendiente por cobrar.',
        ];
        $razon = $e->getMessage();
        http_response_code(isset($mensajes[$razon]) ? 422 : 400);
        echo json_encode([
            'status' => 'error',
            'mensaje' => $mensajes[$razon] ?? 'No se pudo actualizar la reserva.',
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

Logger::registrarLog('WARN', 'Intento de acceso por método no permitido al módulo de editar reservas', [
    'id_usuario' => $idUsuarioLog,
    'metodo' => $_SERVER['REQUEST_METHOD']
]);

http_response_code(405);
echo json_encode(['status' => 'error', 'mensaje' => 'Metodo no permitido']);