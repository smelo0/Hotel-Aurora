<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';

require_once '../configuracion/conexion.php';
require_once '../configuracion/wompi.php';
require_once '../configuracion/permiso.php';

header('Content-Type: application/json; charset=utf-8');
/**@var mysqli $conexion */
if (!isset($_SESSION['user_auth']) && !isset($_SESSION['emp_auth'])) {
    jsonResponse(401, ['status' => 'error', 'mensaje' => 'Debes iniciar sesión para reservar.']);
}

if (!isset($_SESSION['user_auth'])) {
    exigir_permiso($conexion, 'reservas.crear');
}

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

function normalizarMetodoPago(string $metodo): string
{
    $valor = trim($metodo);
    if ($valor === '') {
        return 'Tarjeta';
    }

    $normalizado = strtolower($valor);
    $normalizado = str_replace(['-', '_'], ' ', $normalizado);
    $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT', $normalizado) ?: $normalizado;

    if (str_contains($normalizado, 'recepcion')) {
        return 'Recepción';
    }
    if (str_contains($normalizado, 'transferencia') || str_contains($normalizado, 'pse')) {
        return 'Transferencia';
    }
    if (str_contains($normalizado, 'tarjeta') || str_contains($normalizado, 'credito') || str_contains($normalizado, 'debito')) {
        return 'Tarjeta';
    }
    if (str_contains($normalizado, 'wompi')) {
        return 'Wompi';
    }

    return $valor;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonResponse(405, ['status' => 'error', 'mensaje' => 'Método no permitido.']);
}

exigir_csrf();

$tipoHuesped = trim((string) ($_POST['tipo_huesped'] ?? ''));
$fechaIn = trim((string) ($_POST['fecha_in'] ?? ''));
$fechaOut = trim((string) ($_POST['fecha_out'] ?? ''));
$notasReserva = trim((string) ($_POST['notas_reserva'] ?? ''));
$cantidadAdultos = max(1, (int) ($_POST['cant_adultos'] ?? 1));
$cantidadNinos = max(0, (int) ($_POST['cant_ninos'] ?? 0));
$idHabitacion = (int) ($_POST['id_habitacion'] ?? 0);

// El personal puede registrar una reserva pendiente para pago en recepción.
$metodoRecibido = trim((string) ($_POST['metodo_pago'] ?? ''));
if ($metodoRecibido === '' && isset($_SESSION['emp_auth'])) {
    $metodoRecibido = 'Recepción';
}
$metodoPago = normalizarMetodoPago($metodoRecibido);
$wompiTransactionId = trim((string) ($_POST['wompi_transaction_id'] ?? ''));
$porcentajePago = (int) ($_POST['porcentaje_pago'] ?? 100);
$porcentajePago = in_array($porcentajePago, [50, 100], true) ? $porcentajePago : 100;

if (!in_array($metodoPago, ['Wompi', 'Recepción'], true)) {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona un método de pago válido.']);
}

$secretkey = trim((string) (
    getenv('RECAPTCHA_SECRET_KEY')
    ?: ($_ENV['RECAPTCHA_SECRET_KEY'] ?? $_SERVER['RECAPTCHA_SECRET_KEY'] ?? '')
));
$recapchatoken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
$esPersonal = isset($_SESSION['emp_auth']);
$sitekey = trim((string) (
    getenv('RECAPTCHA_SITE_KEY')
    ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? '')
));

$appEnvironment = strtolower(trim((string) (
    getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'local')
)));
if (!$esPersonal && $secretkey === '' && $appEnvironment === 'production') {
    jsonResponse(503, ['status' => 'error', 'mensaje' => 'La verificación de seguridad no está configurada.']);
}
if (!$esPersonal && $secretkey !== '' && $sitekey === '') {
    jsonResponse(503, ['status' => 'error', 'mensaje' => 'La verificación de seguridad no está configurada correctamente.']);
}

if (!$esPersonal && $recapchatoken === '' && $secretkey !== '') {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'Por favor, completa el reCAPTCHA para continuar.']);
}
 
if (!$esPersonal && $secretkey !== '' && $recapchatoken !== '') {
    $curl = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'secret' => $secretkey,
            'response' => $recapchatoken,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
    ]);
    $verifyresponse = curl_exec($curl);
    $verifyStatus = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $verifyError = curl_error($curl);
    curl_close($curl);

    if ($verifyresponse === false || $verifyError !== '' || $verifyStatus < 200 || $verifyStatus >= 300) {
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

// Solo una transacción aprobada por Wompi puede confirmar una reserva.
$estadoInicial = ($metodoPago === 'Recepción') ? 'Pendiente' : 'Confirmada';
$estadoPago = ($metodoPago === 'Recepción') ? 'Pendiente' : 'Aprobado';
$idUsuarioFinal = 0;
/**@var mysqli $conexion */
try {
    $conexion->begin_transaction();

    $usuarioSesion = $_SESSION['user_auth'] ?? $_SESSION['emp_auth'] ?? [];
    $idUsuarioSesion = (int) ($usuarioSesion['id_usuario'] ?? 0);
    $rolUsuarioSesion = (int) ($usuarioSesion['rol_usuario'] ?? 0);

    if (
        isset($_SESSION['user_auth']['id_usuario'])
        && $rolUsuarioSesion === 6
    ) {
        $idUsuarioFinal = $idUsuarioSesion;
    } elseif ($idUsuarioSesion > 0 && ($tipoHuesped === '' || $rolUsuarioSesion === 6)) {
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
    $montoCalculado = round($precioHabitacion * $noches * 1.19 * ($porcentajePago / 100), 2);
    $montoFinal = $montoCalculado;
    $refFinal = '';

    if ($metodoPago === 'Wompi') {
        if ($wompiTransactionId === '') {
            throw new RuntimeException('wompi_transaction_missing');
        }

        $transaccionWompi = verificarTransaccionWompi($wompiTransactionId, (int) round($montoFinal * 100));
        $refFinal = (string) ($transaccionWompi['id'] ?? '');
        if ($refFinal === '' || $refFinal !== $wompiTransactionId) {
            throw new RuntimeException('wompi_transaction_invalid');
        }

        $stmtPagoDuplicado = $conexion->prepare('SELECT id_pago FROM pagos WHERE referencia_pago = ? LIMIT 1 FOR UPDATE');
        $stmtPagoDuplicado->bind_param('s', $refFinal);
        $stmtPagoDuplicado->execute();
        $stmtPagoDuplicado->store_result();
        $pagoDuplicado = $stmtPagoDuplicado->num_rows > 0;
        $stmtPagoDuplicado->close();
        if ($pagoDuplicado) {
            throw new RuntimeException('wompi_transaction_reused');
        }
    }

    if ($refFinal === '') {
        $refFinal = 'REF-' . bin2hex(random_bytes(16));
    }

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