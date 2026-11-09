<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/sesion_seguridad.php';

require_once '../configuracion/conexion.php';
require_once '../configuracion/wompi.php';
require_once '../configuracion/permiso.php';
require_once __DIR__ . '/../src/Billing/BillingRegistrar.php';
require_once __DIR__ . '/../src/Database/ReceptionGuestSchemaMigrator.php';
require_once __DIR__ . '/../configuracion/capacidad_habitaciones.php';
require_once __DIR__ . '/../src/Reserva/RoomRateSchemaMigrator.php';
require_once __DIR__ . '/../src/Reserva/RoomRateSettings.php';
/**@var mysqli $conexion */
\App\Billing\BillingRegistrar::asegurarEsquema($conexion);

header('Content-Type: application/json; charset=utf-8');
/**@var mysqli $conexion */
if (!isset($_SESSION['user_auth']) && !isset($_SESSION['emp_auth'])) {
    jsonResponse(401, ['status' => 'error', 'mensaje' => 'Debes iniciar sesión para reservar.']);
}

if (!isset($_SESSION['user_auth'])) {
    exigir_permiso($conexion, 'reservas.crear');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
\App\Reserva\RoomRateSchemaMigrator::ensure($conexion);

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
    if (str_contains($normalizado, 'efectivo')) {
        return 'Efectivo';
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
// Adultos y niños se guardan por separado solo como dato informativo: el límite por habitación
// y el precio dependen únicamente del total de personas (adultos + niños).
if (isset($_POST['cant_adultos']) || isset($_POST['cant_ninos'])) {
    $cantidadAdultos = max(1, (int) ($_POST['cant_adultos'] ?? 1));
    $cantidadNinos = max(0, (int) ($_POST['cant_ninos'] ?? 0));
} else {
    $cantidadAdultos = max(1, (int) ($_POST['cant_personas'] ?? 1));
    $cantidadNinos = 0;
}
$cantidadPersonas = $cantidadAdultos + $cantidadNinos;

// Distribución de personas por habitación (portal del huésped con varias habitaciones):
// JSON [{"id": 12, "personas": 3}, ...].
$distribucionHuespedes = null;
$distribucionRaw = $_POST['huespedes_por_habitacion'] ?? '';
if (is_string($distribucionRaw) && trim($distribucionRaw) !== '') {
    $distribucionJson = json_decode($distribucionRaw, true);
    if (!is_array($distribucionJson)) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'La distribución de personas por habitación no es válida.']);
    }
    $distribucionHuespedes = [];
    foreach ($distribucionJson as $filaDistribucion) {
        if (!is_array($filaDistribucion)) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'La distribución de personas por habitación no es válida.']);
        }
        $idDistribucion = (int) ($filaDistribucion['id'] ?? 0);
        if ($idDistribucion < 1 || isset($distribucionHuespedes[$idDistribucion])) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'La distribución de personas por habitación no es válida.']);
        }
        $distribucionHuespedes[$idDistribucion] = [
            'personas' => max(0, (int) ($filaDistribucion['personas'] ?? ((int) ($filaDistribucion['adultos'] ?? 0) + (int) ($filaDistribucion['ninos'] ?? 0)))),
        ];
    }
}
$idHabitacion = (int) ($_POST['id_habitacion'] ?? 0);
$tipoHabitacion = trim((string) ($_POST['tipo_habitacion'] ?? ''));
$cantidadHabitaciones = max(1, (int) ($_POST['cantidad_habitaciones'] ?? 1));

// ⬅️ NUEVO: leer el array de IDs seleccionados por el huésped
$habitacionesIdsPost = $_POST['habitaciones_ids'] ?? [];
if (!is_array($habitacionesIdsPost)) {
    $habitacionesIdsPost = [$habitacionesIdsPost];
}
$habitacionesIdsPost = array_values(array_unique(array_filter(
    array_map('intval', $habitacionesIdsPost),
    static fn(int $id): bool => $id > 0
)));
// ⬆️ FIN NUEVO

$accion = trim((string) ($_POST['accion'] ?? ''));
// La petición es "de personal" solo si viene del panel (envía accion o tipo_huesped).
// Si hay además una sesión de cliente y la petición viene del checkout público
// (sin accion ni tipo_huesped), se trata como reserva de cliente.
$esPersonal = isset($_SESSION['emp_auth']['id_usuario'])
    && ($accion !== '' || $tipoHuesped !== '' || !isset($_SESSION['user_auth']['id_usuario']));
$datosNuevoHuesped = null;
if ($esPersonal && $tipoHuesped === 'nuevo') {
    $nombreNuevoRaw = $_POST['nuevo_nombre'] ?? '';
    $correoNuevoRaw = $_POST['nuevo_correo'] ?? '';
    $tipoDocumentoNuevoRaw = $_POST['nuevo_tipo_documento'] ?? '';
    $documentoNuevoRaw = $_POST['nuevo_documento'] ?? '';
    $telefonoNuevoRaw = $_POST['nuevo_telefono'] ?? '';
    if (
        !is_string($nombreNuevoRaw)
        || !is_string($correoNuevoRaw)
        || !is_string($tipoDocumentoNuevoRaw)
        || !is_string($documentoNuevoRaw)
        || !is_string($telefonoNuevoRaw)
    ) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Revisa los datos ingresados del huésped.']);
    }

    $nombreNuevo = trim($nombreNuevoRaw);
    $correoNuevo = trim($correoNuevoRaw);
    $tipoDocumentoNuevo = trim($tipoDocumentoNuevoRaw);
    $documentoNuevo = trim($documentoNuevoRaw);
    $telefonoNuevo = trim($telefonoNuevoRaw);
    $tiposDocumentoPermitidos = ['CC', 'CE', 'PAS', 'PPT', 'PEP'];
    if (
        ($nombreNuevo === ''
            || preg_match('//u', $nombreNuevo) !== 1
            || preg_match('/[\x00-\x1F\x7F]/u', $nombreNuevo) === 1
            || mb_strlen($nombreNuevo, 'UTF-8') > 100)
        || ($correoNuevo !== '' && (!filter_var($correoNuevo, FILTER_VALIDATE_EMAIL) || strlen($correoNuevo) > 100))
        || !in_array($tipoDocumentoNuevo, $tiposDocumentoPermitidos, true)
        || $documentoNuevo === ''
        || preg_match('/^[0-9]{3,12}$/', $documentoNuevo) !== 1
        || ($telefonoNuevo !== '' && preg_match('/^\+?[0-9 ]{7,20}$/', $telefonoNuevo) !== 1)
    ) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'El nombre, tipo y número de documento son obligatorios; el documento debe tener solo números, entre 3 y 12 dígitos. El correo y teléfono también deben tener un formato válido si los ingresas.']);
    }

    try {
        \App\Database\ReceptionGuestSchemaMigrator::ensure($conexion);
    } catch (Throwable $error) {
        error_log('No se pudo preparar el esquema de huéspedes de recepción: ' . $error->getMessage());
        jsonResponse(500, ['status' => 'error', 'mensaje' => 'No se pudieron guardar los datos opcionales del huésped.']);
    }

    $datosNuevoHuesped = [
        'nombre' => $nombreNuevo,
        'correo' => $correoNuevo !== '' ? $correoNuevo : null,
        'tipo_documento' => $tipoDocumentoNuevo,
        'documento' => $documentoNuevo,
        'telefono' => $telefonoNuevo !== '' ? $telefonoNuevo : null,
    ];
}
$reservaDesdeCuentaHuesped = !$esPersonal
    && isset($_SESSION['user_auth']['id_usuario'])
    && (int) ($_SESSION['user_auth']['rol_usuario'] ?? 0) === 6;
$hospedajeANombreDe = $_POST['hospedaje_a_nombre_de'] ?? 'cuenta';
$nombreHuespedEstadia = '';
if ($reservaDesdeCuentaHuesped) {
    if (!is_string($hospedajeANombreDe) || !in_array($hospedajeANombreDe, ['cuenta', 'otra_persona'], true)) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona a nombre de quién estará el hospedaje.']);
    }
    if ($hospedajeANombreDe === 'otra_persona') {
        $nombreHuespedRaw = $_POST['nombre_huesped_estadia'] ?? null;
        if (!is_string($nombreHuespedRaw)) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'Escribe el nombre de la persona que se hospedará.']);
        }
        $nombreHuespedEstadia = trim($nombreHuespedRaw);
        if (
            $nombreHuespedEstadia === ''
            || preg_match('//u', $nombreHuespedEstadia) !== 1
            || preg_match('/[\x00-\x1F\x7F]/u', $nombreHuespedEstadia) === 1
            || mb_strlen($nombreHuespedEstadia, 'UTF-8') > 100
        ) {
            jsonResponse(422, ['status' => 'error', 'mensaje' => 'El nombre de la persona debe tener entre 1 y 100 caracteres.']);
        }
    }
}

$metodoRecibido = trim((string) ($_POST['metodo_pago'] ?? ''));
if ($esPersonal && $accion === 'guardar') {
    $metodoRecibido = 'Recepción';
}
$metodoPago = normalizarMetodoPago($metodoRecibido);
$wompiTransactionId = trim((string) ($_POST['wompi_transaction_id'] ?? ''));
$porcentajePago = (int) ($_POST['porcentaje_pago'] ?? 100);
$porcentajePago = in_array($porcentajePago, [50, 100], true) ? $porcentajePago : 100;

$metodosPersonal = ['Efectivo', 'Tarjeta', 'Transferencia'];
if ($esPersonal) {
    if (!in_array($accion, ['guardar', 'cobrar'], true)) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona si deseas guardar la reserva o cobrarla.']);
    }
    if (($accion === 'cobrar' && !in_array($metodoPago, $metodosPersonal, true))
        || ($accion === 'guardar' && $metodoPago !== 'Recepción')) {
        jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona un método de pago válido para esta acción.']);
    }
} elseif (!in_array($metodoPago, ['Wompi', 'Recepción'], true)) {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona un método de pago válido.']);
}

$secretkey = trim((string) (
    getenv('RECAPTCHA_SECRET_KEY')
    ?: ($_ENV['RECAPTCHA_SECRET_KEY'] ?? $_SERVER['RECAPTCHA_SECRET_KEY'] ?? '')
));
$recapchatoken = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
$sitekey = trim((string) (
    getenv('RECAPTCHA_SITE_KEY')
    ?: ($_ENV['RECAPTCHA_SITE_KEY'] ?? $_SERVER['RECAPTCHA_SITE_KEY'] ?? '')
));

// reCAPTCHA del checkout: desactivado por defecto.
// Para activarlo: RECAPTCHA_RESERVAS=true en .env (y usar claves reales de Google).
$captchaReservaActivo = filter_var(
    getenv('RECAPTCHA_RESERVAS') ?: ($_ENV['RECAPTCHA_RESERVAS'] ?? $_SERVER['RECAPTCHA_RESERVAS'] ?? 'false'),
    FILTER_VALIDATE_BOOLEAN
);
$verificarCaptcha = !$esPersonal && $captchaReservaActivo;

$appEnvironment = strtolower(trim((string) (
    getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'local')
)));
if ($verificarCaptcha && $secretkey === '' && $appEnvironment === 'production') {
    jsonResponse(503, ['status' => 'error', 'mensaje' => 'La verificación de seguridad no está configurada.']);
}
if ($verificarCaptcha && $secretkey !== '' && $sitekey === '') {
    jsonResponse(503, ['status' => 'error', 'mensaje' => 'La verificación de seguridad no está configurada correctamente.']);
}

if ($verificarCaptcha && $recapchatoken === '' && $secretkey !== '') {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'Por favor, completa el reCAPTCHA para continuar.']);
}

if ($verificarCaptcha && $secretkey !== '' && $recapchatoken !== '') {
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

// ⬅️ ACTUALIZADO: también aceptamos habitacionesIdsPost
if ((!$idHabitacion && $tipoHabitacion === '' && $habitacionesIdsPost === []) || !$checkinDate || !$checkoutDate) {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'Selecciona el tipo de habitación y la cantidad que necesitas.']);
}
if ($cantidadHabitaciones < 1 || $cantidadHabitaciones > 20) {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'La cantidad de habitaciones no es válida.']);
}

if ($checkoutDate <= $checkinDate) {
    jsonResponse(422, ['status' => 'error', 'mensaje' => 'La fecha de salida debe ser posterior al check-in.']);
}

$checkinAt = $checkinDate->setTime(15, 0, 0);
$checkoutAt = $checkoutDate->setTime(12, 0, 0);
$checkinSql  = $checkinAt->format('Y-m-d H:i:s');   // ⬅️ NUEVO (faltaba en tu archivo)
$checkoutSql = $checkoutAt->format('Y-m-d H:i:s');  // ⬅️ NUEVO (faltaba en tu archivo)
$noches = (int) $checkinDate->diff($checkoutDate)->days;

$resumenHabitaciones = $tipoHabitacion !== ''
    ? "Tipo de habitación: {$tipoHabitacion} | Cantidad de habitaciones: {$cantidadHabitaciones}"
    : ($habitacionesIdsPost !== []
        ? 'Habitaciones seleccionadas: ' . count($habitacionesIdsPost)
        : "Habitación seleccionada: {$idHabitacion}");
$notasCompletas = trim(
    "Adultos: {$cantidadAdultos} | Niños: {$cantidadNinos} | Pago: {$metodoPago} | {$resumenHabitaciones}" .
    ($reservaDesdeCuentaHuesped && $hospedajeANombreDe === 'otra_persona'
        ? "\nHospedaje a nombre de: {$nombreHuespedEstadia}"
        : '') .
    ($notasReserva !== '' ? "\n{$notasReserva}" : '')
);

$estadoInicial = ($metodoPago === 'Recepción') ? 'Pendiente' : 'Confirmada';
$estadoPago = ($metodoPago === 'Recepción') ? 'Pendiente' : 'Aprobado';
$idUsuarioFinal = 0;
/**@var mysqli $conexion */
try {
    $conexion->begin_transaction();

    $usuarioSesion = $_SESSION['user_auth'] ?? $_SESSION['emp_auth'] ?? [];
    $idUsuarioSesion = (int) ($usuarioSesion['id_usuario'] ?? 0);
    $rolUsuarioSesion = (int) ($usuarioSesion['rol_usuario'] ?? 0);

    if (isset($_SESSION['user_auth']['id_usuario']) && $rolUsuarioSesion === 6) {
        $idUsuarioFinal = $idUsuarioSesion;
    } elseif (isset($_SESSION['emp_auth']['id_usuario'])) {
        if ($tipoHuesped !== 'nuevo' && ctype_digit($tipoHuesped) && (int) $tipoHuesped > 0) {
            $idHuespedSeleccionado = (int) $tipoHuesped;
            $rolHuesped = 6;
            $stmtHuesped = $conexion->prepare(
                'SELECT id_usu FROM usuario WHERE id_usu = ? AND cod_rol_usu = ? AND est_usu = 1 LIMIT 1'
            );
            $stmtHuesped->bind_param('ii', $idHuespedSeleccionado, $rolHuesped);
            $stmtHuesped->execute();
            $stmtHuesped->bind_result($idHuespedEncontrado);
            if ($stmtHuesped->fetch()) {
                $idUsuarioFinal = (int) $idHuespedEncontrado;
            }
            $stmtHuesped->close();
        } elseif ($tipoHuesped === 'nuevo') {
            $correoNuevo = $datosNuevoHuesped['correo'];
            if ($correoNuevo !== null) {
                $stmtUsuarioExistente = $conexion->prepare(
                    'SELECT id_usu, cod_rol_usu FROM usuario WHERE corr_usu = ? LIMIT 1'
                );
                $stmtUsuarioExistente->bind_param('s', $correoNuevo);
                $stmtUsuarioExistente->execute();
                $stmtUsuarioExistente->bind_result($idUsuarioEncontrado, $rolEncontrado);
                if ($stmtUsuarioExistente->fetch()) {
                    $stmtUsuarioExistente->close();
                    if ((int) $rolEncontrado !== 6) {
                        throw new RuntimeException('datos_huesped_invalidos');
                    }
                    $idUsuarioFinal = (int) $idUsuarioEncontrado;
                } else {
                    $stmtUsuarioExistente->close();
                }
            }
            if ($idUsuarioFinal === 0) {
                $passwordGenerica = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
                $rolHuesped = 6;
                $sqlNuevoUsuario = 'INSERT INTO usuario (nom_usu, doc_usu, tipo_doc_usu, tel_usu, corr_usu, psw_usu, cod_rol_usu)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)';
                $stmtNuevoUsuario = $conexion->prepare($sqlNuevoUsuario);
                $stmtNuevoUsuario->bind_param(
                    'ssssssi',
                    $datosNuevoHuesped['nombre'],
                    $datosNuevoHuesped['documento'],
                    $datosNuevoHuesped['tipo_documento'],
                    $datosNuevoHuesped['telefono'],
                    $datosNuevoHuesped['correo'],
                    $passwordGenerica,
                    $rolHuesped
                );
                $stmtNuevoUsuario->execute();
                $idUsuarioFinal = (int) $conexion->insert_id;
                $stmtNuevoUsuario->close();
            }
        } else {
            throw new RuntimeException('datos_huesped_invalidos');
        }
    } else {
        $nombreNuevo = trim((string) ($_POST['nuevo_nombre'] ?? ''));
        $correoNuevo = trim((string) ($_POST['nuevo_correo'] ?? ''));

        if ($nombreNuevo === '' || !filter_var($correoNuevo, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('datos_huesped_invalidos');
        }

        $sqlUsuarioExistente = 'SELECT id_usu, cod_rol_usu FROM usuario WHERE corr_usu = ? LIMIT 1';
        $stmtUsuarioExistente = $conexion->prepare($sqlUsuarioExistente);
        $stmtUsuarioExistente->bind_param('s', $correoNuevo);
        $stmtUsuarioExistente->execute();
        $stmtUsuarioExistente->bind_result($idUsuarioEncontrado, $rolEncontrado);

        if ($stmtUsuarioExistente->fetch()) {
            $stmtUsuarioExistente->close();
            if ((int) $rolEncontrado !== 6) {
                throw new RuntimeException('datos_huesped_invalidos');
            }
            $idUsuarioFinal = (int) $idUsuarioEncontrado;
        } else {
            $stmtUsuarioExistente->close();

            $passwordGenerica = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
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

    $stmtDatosHuesped = $conexion->prepare('SELECT nom_usu, corr_usu FROM usuario WHERE id_usu = ? LIMIT 1');
    $stmtDatosHuesped->bind_param('i', $idUsuarioFinal);
    $stmtDatosHuesped->execute();
    $stmtDatosHuesped->bind_result($nombreHuespedFactura, $correoHuespedFactura);
    if (!$stmtDatosHuesped->fetch()) {
        $stmtDatosHuesped->close();
        throw new RuntimeException('usuario_invalido');
    }
    $stmtDatosHuesped->close();

    $habitacionesSeleccionadas = [];

    // ═══════════════════════════════════════════════════════════════
    // RAMA 1 (NUEVA): el huésped eligió varias habitaciones específicas
    // ═══════════════════════════════════════════════════════════════
    if ($habitacionesIdsPost !== []) {
        $placeholders = implode(',', array_fill(0, count($habitacionesIdsPost), '?'));
        $sqlRooms = "SELECT cod_hab, num_hab, tipo_hab,
                            COALESCE(NULLIF(pre_hab, 0), precio_hab, 0) AS precio_habitacion,
                            est_hab
                     FROM habitacion
                     WHERE cod_hab IN ($placeholders)
                       AND est_hab NOT IN ('Mantenimiento', 'Sucia')
                     FOR UPDATE";
        $stmtRooms = $conexion->prepare($sqlRooms);
        $types = str_repeat('i', count($habitacionesIdsPost));
        $stmtRooms->bind_param($types, ...$habitacionesIdsPost);
        $stmtRooms->execute();
        $roomsResult = $stmtRooms->get_result();

        while ($room = $roomsResult->fetch_assoc()) {
            \App\Reserva\RoomAvailabilityService::validar(
                $conexion,
                (int) $room['cod_hab'],
                $checkinAt,
                $checkoutAt
            );
            $habitacionesSeleccionadas[] = [
                'cod_hab'  => (int) $room['cod_hab'],
                'num_hab'  => (int) $room['num_hab'],
                'tipo_hab' => (string) $room['tipo_hab'],
                'precio'   => (float) $room['precio_habitacion'],
                'est_hab'  => (string) $room['est_hab'],
            ];
        }
        $stmtRooms->close();

        if (count($habitacionesSeleccionadas) !== count($habitacionesIdsPost)) {
            throw new RuntimeException('habitaciones_insuficientes');
        }

        $tipoHabitacion = count($habitacionesSeleccionadas) > 1
            ? 'Múltiples'
            : (string) ($habitacionesSeleccionadas[0]['tipo_hab'] ?? '');
        $cantidadHabitaciones = count($habitacionesSeleccionadas);
    }

    // ═══════════════════════════════════════════════════════════════
    // RAMA 2: tipo de habitación + cantidad (flujo viejo, intacto)
    // ═══════════════════════════════════════════════════════════════
    elseif ($tipoHabitacion !== '') {
        $sqlRooms = "SELECT cod_hab, num_hab, tipo_hab,
                            COALESCE(NULLIF(pre_hab, 0), precio_hab, 0) AS precio_habitacion,
                            est_hab
                     FROM habitacion
                     WHERE tipo_hab = ?
                       AND est_hab NOT IN ('Mantenimiento', 'Sucia')
                     ORDER BY num_hab ASC
                     FOR UPDATE";
        $stmtRooms = $conexion->prepare($sqlRooms);
        $stmtRooms->bind_param('s', $tipoHabitacion);
        $stmtRooms->execute();
        $roomsResult = $stmtRooms->get_result();

        while ($room = $roomsResult->fetch_assoc()) {
            if (count($habitacionesSeleccionadas) >= $cantidadHabitaciones) {
                break;
            }

            $roomId = (int) $room['cod_hab'];

            try {
                \App\Reserva\RoomAvailabilityService::validar(
                    $conexion,
                    $roomId,
                    $checkinAt,
                    $checkoutAt
                );
                $habitacionesSeleccionadas[] = [
                    'cod_hab'  => $roomId,
                    'num_hab'  => (int) $room['num_hab'],
                    'tipo_hab' => (string) $room['tipo_hab'],
                    'precio'   => (float) $room['precio_habitacion'],
                    'est_hab'  => (string) $room['est_hab'],
                ];
            } catch (Throwable $roomError) {
                if (!in_array($roomError->getMessage(), ['habitacion_reservada', 'habitacion_no_disponible'], true)) {
                    $stmtRooms->close();
                    throw $roomError;
                }
            }
        }
        $stmtRooms->close();
    }

    // ═══════════════════════════════════════════════════════════════
    // RAMA 3: id de habitación específica (compatibilidad)
    // ═══════════════════════════════════════════════════════════════
    elseif ($idHabitacion > 0) {
        $sqlRoom = 'SELECT cod_hab, num_hab, tipo_hab,
                           COALESCE(NULLIF(pre_hab, 0), precio_hab, 0) AS precio_habitacion,
                           est_hab
                    FROM habitacion WHERE cod_hab = ? LIMIT 1 FOR UPDATE';
        $stmtRoom = $conexion->prepare($sqlRoom);
        $stmtRoom->bind_param('i', $idHabitacion);
        $stmtRoom->execute();
        $room = $stmtRoom->get_result()->fetch_assoc();
        $stmtRoom->close();

        if (!$room) {
            throw new RuntimeException('habitacion_no_existe');
        }

        \App\Reserva\RoomAvailabilityService::validar(
            $conexion,
            $idHabitacion,
            $checkinAt,
            $checkoutAt
        );

        $habitacionesSeleccionadas[] = [
            'cod_hab'  => (int) $room['cod_hab'],
            'num_hab'  => (int) $room['num_hab'],
            'tipo_hab' => (string) $room['tipo_hab'],
            'precio'   => (float) $room['precio_habitacion'],
            'est_hab'  => (string) $room['est_hab'],
        ];
        $tipoHabitacion = (string) $room['tipo_hab'];
        $cantidadHabitaciones = 1;
    }

    if (count($habitacionesSeleccionadas) < $cantidadHabitaciones) {
        throw new RuntimeException('habitaciones_insuficientes');
    }

    // Límite de personas por habitación y reparto entre habitaciones.
    if (count($habitacionesSeleccionadas) > 1 && empty($distribucionHuespedes)) {
        throw new RuntimeException('ocupacion_invalida:Indica cuántas personas habrá en cada una de las habitaciones.');
    }
    $distribucionValidada = validar_ocupacion_habitaciones(
        $habitacionesSeleccionadas,
        $cantidadPersonas,
        count($habitacionesSeleccionadas) > 1 ? $distribucionHuespedes : null
    );
    $cargoPersonaAdicional = \App\Reserva\RoomRateSettings::additionalPersonSupplement($conexion);
    foreach ($habitacionesSeleccionadas as &$habitacionTarificada) {
        $personasHabitacion = (int) ($distribucionValidada[$habitacionTarificada['cod_hab']]['personas'] ?? $cantidadPersonas);
        $habitacionTarificada['precio'] = \App\Reserva\RoomRateCalculator::nightlyRate(
            (string) $habitacionTarificada['tipo_hab'],
            (float) $habitacionTarificada['precio'],
            $personasHabitacion,
            $cargoPersonaAdicional
        );
    }
    unset($habitacionTarificada);

    if ($distribucionValidada !== [] && count($habitacionesSeleccionadas) > 1) {
        $detalleDistribucion = [];
        foreach ($habitacionesSeleccionadas as $habitacionDistribuida) {
            $personasEnHabitacion = (int) $distribucionValidada[$habitacionDistribuida['cod_hab']]['personas'];
            $detalleDistribucion[] = 'Hab. ' . $habitacionDistribuida['num_hab'] . ' ('
                . $personasEnHabitacion . ($personasEnHabitacion === 1 ? ' persona' : ' personas') . ')';
        }
        $notasCompletas .= "\nDistribución de personas: " . implode('; ', $detalleDistribucion);
    }

    $precioNocheTotal = array_sum(array_map(
        static fn(array $room): float => (float) $room['precio'],
        $habitacionesSeleccionadas
    ));
    $numerosHabitaciones = array_map(
        static fn(array $room): string => (string) $room['num_hab'],
        $habitacionesSeleccionadas
    );
    $habitacionesTexto = implode(', ', $numerosHabitaciones);

    // 1. Insertar la Reserva
    $sqlReserva = 'INSERT INTO reservas (fec_ent_res, fec_sal_res, est_res, not_res, id_usu_res) VALUES (?, ?, ?, ?, ?)';
    $stmtReserva = $conexion->prepare($sqlReserva);
    $stmtReserva->bind_param('ssssi', $checkinSql, $checkoutSql, $estadoInicial, $notasCompletas, $idUsuarioFinal);
    $stmtReserva->execute();
    $idReservaNueva = (int) $conexion->insert_id;
    $stmtReserva->close();

    // 2. Insertar un detalle por cada habitación.
    $sqlDetalle = 'INSERT INTO detalle (can_noc_det, cod_res_det, cod_hab_det, precio_noche_det) VALUES (?, ?, ?, ?)';
    $stmtDetalle = $conexion->prepare($sqlDetalle);
    foreach ($habitacionesSeleccionadas as $room) {
        $roomIdDetalle = (int) $room['cod_hab'];
        $precioNocheHabitacion = (float) $room['precio'];
        $stmtDetalle->bind_param('iiid', $noches, $idReservaNueva, $roomIdDetalle, $precioNocheHabitacion);
        $stmtDetalle->execute();
    }
    $stmtDetalle->close();

    // 3. Registrar el Pago
    $precioHabitacion = (float) $precioNocheTotal;
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
    $idPagoNuevo = (int) $conexion->insert_id;
    $stmtPago->close();

    // 4. Actualizar estado de cada habitación si la estancia ya está en curso.
    $today = new DateTimeImmutable('today');
    if ($checkinDate <= $today && $checkoutDate > $today) {
        $newStatus = 'Ocupada';
        $stmtEstado = $conexion->prepare('UPDATE habitacion SET est_hab = ? WHERE cod_hab = ?');
        foreach ($habitacionesSeleccionadas as $room) {
            if ((string) $room['est_hab'] === 'Disponible') {
                $roomIdEstado = (int) $room['cod_hab'];
                $stmtEstado->bind_param('si', $newStatus, $roomIdEstado);
                $stmtEstado->execute();
            }
        }
        $stmtEstado->close();
    }

    if ($estadoPago === 'Aprobado') {
        \App\Billing\BillingRegistrar::registrarFactura($conexion, 'FAC-' . $idPagoNuevo, 'alojamiento', $idReservaNueva, (float) $montoFinal, (string) $metodoPago, [
            'folio' => 'FAC-' . $idPagoNuevo,
            'cod_reserva' => $idReservaNueva,
            'huesped' => (string) $nombreHuespedFactura,
            'correo' => (string) $correoHuespedFactura,
            'habitacion' => $habitacionesTexto,
            'fecha_entrada' => $checkinSql,
            'fecha_salida' => $checkoutSql,
            'noches' => $noches,
            'metodo_pago' => (string) $metodoPago,
            'monto' => (float) $montoFinal,
        ]);
    }

    $conexion->commit();

    jsonResponse(200, [
        'status' => 'exito',
        'mensaje' => 'Reserva creada correctamente.',
        'reserva' => [
            'cod_res' => $idReservaNueva,
            'nom_usu' => (string) $nombreHuespedFactura,
            'fec_ent_res' => $checkinSql,
            'fec_sal_res' => $checkoutSql,
            'est_res' => $estadoInicial,
            'not_res' => $notasCompletas,
            'cod_hab_det' => (int) ($habitacionesSeleccionadas[0]['cod_hab'] ?? 0),
            'cantidad_habitaciones' => count($habitacionesSeleccionadas),
            'tipo_habitacion' => $tipoHabitacion,
            'habitaciones' => array_map(
                static fn(array $room): array => [
                    'cod_hab' => (int) $room['cod_hab'],
                    'num_hab' => (int) $room['num_hab'],
                    'tipo_hab' => (string) $room['tipo_hab'],
                    'precio' => (float) $room['precio'],
                ],
                $habitacionesSeleccionadas
            ),
            'habitacion' => [
                'cod_hab' => (int) ($habitacionesSeleccionadas[0]['cod_hab'] ?? 0),
                'num_hab' => (int) ($habitacionesSeleccionadas[0]['num_hab'] ?? 0),
                'tipo_hab' => $tipoHabitacion,
                'precio' => $precioHabitacion,
            ],
            'pago' => [
                'monto' => $montoFinal,
                'metodo' => $metodoPago,
                'referencia' => $refFinal,
                'estado' => $estadoPago
            ],
            'factura' => $estadoPago === 'Aprobado' ? [
                'folio' => 'FAC-' . $idPagoNuevo,
                'cod_reserva' => $idReservaNueva,
                'fecha' => date('Y-m-d H:i:s'),
                'huesped' => (string) $nombreHuespedFactura,
                'correo' => (string) $correoHuespedFactura,
                'habitacion' => $habitacionesTexto,
                'tipo_habitacion' => $tipoHabitacion . ' · ' . count($habitacionesSeleccionadas) . ' habitación(es)',
                'fecha_entrada' => $checkinSql,
                'fecha_salida' => $checkoutSql,
                'noches' => $noches,
                'metodo_pago' => $metodoPago,
                'monto' => $montoFinal,
            ] : null,
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
        $message = 'Revisa los datos del huésped. El correo debe tener un formato válido si lo ingresas.';
        $statusCode = 422;
    } elseif ($reason === 'habitacion_no_existe') {
        $message = 'La habitación seleccionada no existe.';
        $statusCode = 404;
    } elseif ($reason === 'habitacion_no_disponible') {
        $message = 'La habitación no está disponible para reservas en este momento.';
        $statusCode = 409;
    } elseif ($reason === 'habitacion_reservada') {
        $message = 'Una de las habitaciones seleccionadas ya está reservada para esas fechas.';
        $statusCode = 409;
    } elseif ($reason === 'habitaciones_insuficientes') {
        $message = 'No hay suficientes habitaciones de ese tipo disponibles para las fechas seleccionadas.';
        $statusCode = 409;
    } elseif (str_starts_with($reason, 'ocupacion_invalida:')) {
        $message = substr($reason, strlen('ocupacion_invalida:'));
        $statusCode = 422;
    } elseif (str_starts_with($reason, 'wompi_')) {
        $message = match ($reason) {
            'wompi_private_key_missing' => 'Falta configurar la llave privada de Wompi en el servidor.',
            'wompi_transaction_not_approved' => 'El pago no fue aprobado por Wompi.',
            'wompi_transaction_amount_mismatch' => 'El monto recibido por Wompi no coincide con la reserva.',
            default => 'No fue posible verificar el pago con Wompi.',
        };
        $statusCode = 422;
    }

    error_log('GUARDAR_RESERVA_ERROR: ' . $reason . ' | ' . $error->getFile() . ':' . $error->getLine()); // ⬅️ NUEVO (útil para depurar)

    jsonResponse($statusCode, [
        'status' => 'error',
        'mensaje' => $message,
    ]);
}