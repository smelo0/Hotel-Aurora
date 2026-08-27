<?php
declare(strict_types=1);

session_start();

require_once '../configuracion/conexion.php';
require_once '../configuracion/wompi.php';

header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function jsonResponse(int $statusCode, array $payload): never
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit();
}

function parseDateOnly(string $value): ?DateTimeImmutable
{
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    if (!$date) {
        return null;
    }

    $errors = DateTimeImmutable::getLastErrors();
    if (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
        return null;
    }

    return $date;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(405, ['status' => 'error', 'mensaje' => 'Método no permitido.']);
}

$tipoHuesped = trim((string) ($_POST['tipo_huesped'] ?? ''));
$fechaIn = trim((string) ($_POST['fecha_in'] ?? ''));
$fechaOut = trim((string) ($_POST['fecha_out'] ?? ''));
$notasReserva = trim((string) ($_POST['notas_reserva'] ?? ''));
$cantidadAdultos = max(1, (int) ($_POST['cant_adultos'] ?? 1));
$cantidadNinos = max(0, (int) ($_POST['cant_ninos'] ?? 0));
$idHabitacion = (int) ($_POST['id_habitacion'] ?? 0);

// Nuevos parámetros recibidos desde la pasarela / modal de pago
$metodoPago = trim((string) ($_POST['metodo_pago'] ?? 'Tarjeta'));
$referenciaPago = trim((string) ($_POST['referencia_pago'] ?? ''));
$totalReserva = (float) ($_POST['total_reserva'] ?? 0.0);
$wompiTransactionId = trim((string) ($_POST['wompi_transaction_id'] ?? ''));
$porcentajePago = (int) ($_POST['porcentaje_pago'] ?? 100);
$porcentajePago = in_array($porcentajePago, [50, 100], true) ? $porcentajePago : 100;

$secretkey = trim((string) (getenv('RECAPTCHA_SECRET_KEY') ?: ''));
$recapchatoken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
  
if ($recapchatoken === '' && $secretkey !== '') {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'Por favor, completa el reCAPTCHA para continuar.']);
}
 
$secretkey = $secretkey;
//agregar la verificación del reCAPTCHA 
//forma grafica de verificar el reCAPTCHA
///                            









if ($secretkey !== '' && $recapchatoken !== '') {
    $verifyurl = 'https://www.google.com/recaptcha/api/siteverify';
    $verifyresponse = file_get_contents($verifyurl . '?secret=' . urlencode($secretkey));

    if ($verifyresponse === false) {
        jsonResponse(500, ['status' => 'error', 'mensaje' => 'Error al verificar reCAPTCHA.']);
    }

    $responseData = json_decode($verifyresponse, true);
    if (!isset($responseData['success']) || $responseData['success'] !== true) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'reCAPTCHA no verificado.']);
    }
}

$checkinDate = parseDateOnly($fechaIn);
$checkoutDate = parseDateOnly($fechaOut);

if ($idHabitacion <= 0 || !$checkinDate || !$checkoutDate) {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'Los datos de la reserva no son válidos.']);
}

if ($checkoutDate <= $checkinDate) {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'La fecha de salida debe ser posterior al check-in.']);
}

$checkinAt = $checkinDate->setTime(15, 0, 0);
$checkoutAt = $checkoutDate->setTime(12, 0, 0);
$noches = (int) $checkinDate->diff($checkoutDate)->days;
$notasCompletas = trim(
    "Adultos: {$cantidadAdultos} | Niños: {$cantidadNinos} | Pago: {$metodoPago}" .
    ($notasReserva !== '' ? "\n{$notasReserva}" : '')
);

// Ajuste dinámico del estado según el medio de pago
$estadoInicial = ($metodoPago === 'Recepción') ? 'Pendiente' : 'Confirmada';
$estadoPago = ($metodoPago === 'Recepción') ? 'Pendiente' : 'Aprobado';
$idUsuarioFinal = 0;
/**@var mysqli $conexion */
try {
    $conexion->begin_transaction();

    $idUsuarioSesion = (int) ($_SESSION['user_auth']['id_usuario'] ?? 0);

    if ($idUsuarioSesion > 0 && $tipoHuesped === '') {
        $idUsuarioFinal = $idUsuarioSesion;
    } else {
        $nombreNuevo = trim((string) ($_POST['nuevo_nombre'] ?? ''));
        $correoNuevo = trim((string) ($_POST['nuevo_correo'] ?? ''));

        if ($nombreNuevo === '' || !filter_var($correoNuevo, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('datos_huesped_invalidos');
        }

        $sqlUsuarioExistente = 'SELECT id_usu FROM usuario WHERE corr_usu = ? LIMIT 1';
        $stmtUsuarioExistente = $conexion->prepare($sqlUsuarioExistente);
        $stmtUsuarioExistente->bind_param('s', $correoNuevo);
        $stmtUsuarioExistente->execute();
        $stmtUsuarioExistente->store_result();

        if ($stmtUsuarioExistente->num_rows > 0) {
            $stmtUsuarioExistente->bind_result($idUsuarioEncontrado);
            $stmtUsuarioExistente->fetch();
            $idUsuarioFinal = (int) $idUsuarioEncontrado;
            $stmtUsuarioExistente->close();
        } else {
            $stmtUsuarioExistente->close();

            $passwordGenerica = password_hash('Aurora2026', PASSWORD_BCRYPT);
            $rolHuesped = 6;
            $sqlNuevoUsuario = 'INSERT INTO usuario (nom_usu, corr_usu, psw_usu, cod_rol_usu) VALUES (?, ?, ?, ?)';
            $stmtNuevoUsuario = $conexion->prepare($sqlNuevoUsuario);
            $stmtNuevoUsuario->bind_param('sssi', $nombreNuevo, $correoNuevo, $passwordGenerica, $rolHuesped);
            $stmtNuevoUsuario->execute();
            $idUsuarioFinal = (int) $conexion->insert_id;
            $stmtNuevoUsuario->close();
        }
    }

    if ($idUsuarioFinal <= 0) {
        throw new RuntimeException('usuario_invalido');
    }

    $sqlRoom = 'SELECT cod_hab, num_hab, tipo_hab, pre_hab, precio_hab, est_hab FROM habitacion WHERE cod_hab = ? LIMIT 1 FOR UPDATE';
    $stmtRoom = $conexion->prepare($sqlRoom);
    $stmtRoom->bind_param('i', $idHabitacion);
    $stmtRoom->execute();
    $stmtRoom->store_result();

    if ($stmtRoom->num_rows !== 1) {
        $stmtRoom->close();
        throw new RuntimeException('habitacion_no_existe');
    }

    $stmtRoom->bind_result($roomIdDb, $roomNumber, $roomType, $roomPricePrimary, $roomPriceSecondary, $roomStatus);
    $stmtRoom->fetch();
    $stmtRoom->close();

    if (in_array((string) $roomStatus, ['Mantenimiento', 'Sucia'], true)) {
        throw new RuntimeException('habitacion_no_disponible');
    }

    $checkinSql = $checkinAt->format('Y-m-d H:i:s');
    $checkoutSql = $checkoutAt->format('Y-m-d H:i:s');

    $sqlOverlap = "SELECT r.cod_res
                   FROM detalle d
                   INNER JOIN reservas r ON r.cod_res = d.cod_res_det
                   WHERE d.cod_hab_det = ?
                     AND r.est_res NOT IN ('Cancelada', 'Cancelado', 'Finalizada')
                     AND ? < r.fec_sal_res
                     AND ? > r.fec_ent_res
                   LIMIT 1";
    $stmtOverlap = $conexion->prepare($sqlOverlap);
    $stmtOverlap->bind_param('iss', $idHabitacion, $checkinSql, $checkoutSql);
    $stmtOverlap->execute();
    $stmtOverlap->store_result();

    if ($stmtOverlap->num_rows > 0) {
        $stmtOverlap->close();
        throw new RuntimeException('habitacion_reservada');
    }

    $stmtOverlap->close();

    // 1. Insertar la Reserva
    $sqlReserva = 'INSERT INTO reservas (fec_ent_res, fec_sal_res, est_res, not_res, id_usu_res) VALUES (?, ?, ?, ?, ?)';
    $stmtReserva = $conexion->prepare($sqlReserva);
    $stmtReserva->bind_param('ssssi', $checkinSql, $checkoutSql, $estadoInicial, $notasCompletas, $idUsuarioFinal);
    $stmtReserva->execute();
    $idReservaNueva = (int) $conexion->insert_id;
    $stmtReserva->close();

    // 2. Insertar el Detalle de la Reserva
    $sqlDetalle = 'INSERT INTO detalle (can_noc_det, cod_res_det, cod_hab_det) VALUES (?, ?, ?)';
    $stmtDetalle = $conexion->prepare($sqlDetalle);
    $stmtDetalle->bind_param('iii', $noches, $idReservaNueva, $idHabitacion);
    $stmtDetalle->execute();
    $stmtDetalle->close();

    // 3. Registrar el Pago en la Base de Datos
    $precioHabitacion = (float) ($roomPricePrimary ?: $roomPriceSecondary ?: 0);
    $montoCalculado = $precioHabitacion * $noches * 1.19 * ($porcentajePago / 100);
    $montoFinal = $totalReserva > 0 ? $totalReserva : $montoCalculado;

    if ($metodoPago === 'Wompi') {
        if ($wompiTransactionId === '') {
            throw new RuntimeException('wompi_transaction_missing');
        }

        $montoFinal = round($montoCalculado, 2);
        $transaccionWompi = verificarTransaccionWompi($wompiTransactionId, (int) round($montoFinal * 100));
        $referenciaPago = (string) ($transaccionWompi['reference'] ?? '');
    }

    $refFinal = $referenciaPago !== '' ? $referenciaPago : ('REF-' . time() . '-' . $idReservaNueva);

    $sqlPago = 'INSERT INTO pagos (cod_res_pago, monto, metodo_pago, referencia_pago, estado_pago) VALUES (?, ?, ?, ?, ?)';
    $stmtPago = $conexion->prepare($sqlPago);
    $stmtPago->bind_param('idsss', $idReservaNueva, $montoFinal, $metodoPago, $refFinal, $estadoPago);
    $stmtPago->execute();
    $stmtPago->close();

    // 4. Actualizar Estado de la Habitación si corresponde
    $today = new DateTimeImmutable('today');
    if ($checkinDate <= $today && $checkoutDate > $today && (string) $roomStatus === 'Disponible') {
        $newStatus = 'Ocupada';
        $stmtEstado = $conexion->prepare('UPDATE habitacion SET est_hab = ? WHERE cod_hab = ?');
        $stmtEstado->bind_param('si', $newStatus, $idHabitacion);
        $stmtEstado->execute();
        $stmtEstado->close();
    }

    // Si todo salió bien, guardamos permanentemente
    $conexion->commit();

    jsonResponse(200, [
        'status' => 'exito',
        'mensaje' => 'Reserva creada correctamente.',
        'reserva' => [
            'cod_res' => $idReservaNueva,
            'fec_ent_res' => $checkinSql,
            'fec_sal_res' => $checkoutSql,
            'est_res' => $estadoInicial,
            'not_res' => $notasCompletas,
            'cod_hab_det' => $idHabitacion,
            'habitacion' => [
                'cod_hab' => (int) $roomIdDb,
                'num_hab' => (int) $roomNumber,
                'tipo_hab' => (string) $roomType,
                'precio' => $precioHabitacion,
            ],
            'pago' => [
                'monto' => $montoFinal,
                'metodo' => $metodoPago,
                'referencia' => $refFinal,
                'estado' => $estadoPago
            ]
        ],
    ]);
} catch (Throwable $error) {
    try {
        $conexion->rollback();
    } catch (Throwable $rollbackError) {
    }

    $reason = $error->getMessage();
    $message = 'No se pudo crear la reserva.';
    $statusCode = 400;

    if (in_array($reason, ['datos_huesped_invalidos', 'usuario_invalido'], true)) {
        $message = 'Completa un nombre y un correo válidos para continuar.';
        $statusCode = 422;
    } elseif ($reason === 'habitacion_no_existe') {
        $message = 'La habitación seleccionada no existe.';
        $statusCode = 404;
    } elseif ($reason === 'habitacion_no_disponible') {
        $message = 'La habitación no está disponible para reservas en este momento.';
        $statusCode = 409;
    } elseif ($reason === 'habitacion_reservada') {
        $message = 'La habitación ya está reservada para esas fechas.';
        $statusCode = 409;
    } elseif (str_starts_with($reason, 'wompi_')) {
        $message = match ($reason) {
            'wompi_private_key_missing' => 'Falta configurar la llave privada de Wompi en el servidor.',
            'wompi_transaction_not_approved' => 'El pago no fue aprobado por Wompi.',
            'wompi_transaction_amount_mismatch' => 'El monto recibido por Wompi no coincide con la reserva.',
            default => 'No fue posible verificar el pago con Wompi.',
        };
        $statusCode = 422;
    }

    jsonResponse($statusCode, [
        'status' => 'error',
        'mensaje' => $message,
    ]);
}